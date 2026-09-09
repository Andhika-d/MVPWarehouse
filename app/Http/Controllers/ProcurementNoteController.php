<?php

namespace App\Http\Controllers;

use App\Exports\ProcurementNoteExport;
use App\Models\AuditLog;
use App\Models\ProcurementNote;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\ProcurementNoteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProcurementNoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validateNote($request);

        $note = DB::transaction(function () use ($data) {
            $requests = $this->lockAvailableRequests($data['request_ids']);
            abort_unless($requests->count() === count(array_unique($data['request_ids'])), 422, 'Sebagian request sudah masuk nota lain atau tidak dapat diproses.');

            $note = ProcurementNote::create([
                'status' => ProcurementNote::STATUS_DRAFT,
                'created_by' => Auth::id(),
                'driver_name' => $data['driver_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $note->update(['number' => $this->nextNumber()]);
            $this->replaceItems($note, $requests);
            $this->audit('procurement_note_created', $note, 'Membuat draft '.$note->number.' dengan '.$requests->count().' item');

            return $note;
        });

        return redirect()->route('hr.procurement-notes.show', $note)->with('success', 'Draft nota berhasil dibuat. Periksa isi lalu terbitkan.');
    }

    public function show(ProcurementNote $procurementNote)
    {
        $procurementNote->load(['items.stockRequest', 'creator', 'canceller']);
        $availableRequests = collect();
        if ($procurementNote->isDraft()) {
            $availableRequests = StockRequest::with(['item', 'user'])
                ->where('status', 'Disetujui')
                ->where(function ($query) use ($procurementNote) {
                    $query->whereNull('procurement_note_id')->orWhere('procurement_note_id', $procurementNote->id);
                })->latest()->get();
        }

        return view('hr.procurement-notes.show', compact('procurementNote', 'availableRequests'));
    }

    public function update(Request $request, ProcurementNote $procurementNote)
    {
        abort_unless($procurementNote->isDraft(), 422, 'Nota yang sudah diterbitkan tidak dapat diedit.');
        $data = $this->validateNote($request);

        DB::transaction(function () use ($data, $procurementNote) {
            $note = ProcurementNote::lockForUpdate()->findOrFail($procurementNote->id);
            abort_unless($note->isDraft(), 422, 'Nota yang sudah diterbitkan tidak dapat diedit.');
            $requests = $this->lockAvailableRequests($data['request_ids'], $note->id);
            abort_unless($requests->count() === count(array_unique($data['request_ids'])), 422, 'Sebagian request sudah masuk nota lain atau tidak dapat diproses.');

            StockRequest::where('procurement_note_id', $note->id)->update(['procurement_note_id' => null]);
            $note->items()->delete();
            $note->update(['driver_name' => $data['driver_name'] ?? null, 'notes' => $data['notes'] ?? null]);
            $this->replaceItems($note, $requests);
            $this->audit('procurement_note_updated', $note, 'Memperbarui draft '.$note->number);
        });

        return back()->with('success', 'Draft nota berhasil diperbarui.');
    }

    public function issue(ProcurementNote $procurementNote)
    {
        DB::transaction(function () use ($procurementNote) {
            $note = ProcurementNote::lockForUpdate()->findOrFail($procurementNote->id);
            abort_unless($note->isDraft(), 422, 'Nota ini sudah diterbitkan atau dibatalkan.');
            abort_if($note->items()->doesntExist(), 422, 'Nota harus memiliki minimal satu item.');
            $note->update(['status' => ProcurementNote::STATUS_ISSUED, 'issued_at' => now()]);
            $this->audit('procurement_note_issued', $note, 'Menerbitkan '.$note->number);
            $this->notifyWarehouse($note, 'issued');
        });

        return back()->with('success', 'Nota berhasil diterbitkan dan isinya telah dikunci.');
    }

    public function cancel(Request $request, ProcurementNote $procurementNote)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($data, $procurementNote) {
            $note = ProcurementNote::lockForUpdate()->findOrFail($procurementNote->id);
            abort_if(in_array($note->status, [ProcurementNote::STATUS_COMPLETED, ProcurementNote::STATUS_CANCELLED], true), 422, 'Nota ini sudah selesai atau dibatalkan.');
            abort_if($note->items()->where('received_quantity', '>', 0)->exists(), 422, 'Nota yang sudah memiliki penerimaan tidak dapat dibatalkan.');

            StockRequest::where('procurement_note_id', $note->id)->update(['procurement_note_id' => null]);
            $note->items()->update(['request_status' => 'Dibatalkan']);
            $note->update([
                'status' => ProcurementNote::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $data['reason'],
            ]);
            $this->audit('procurement_note_cancelled', $note, 'Membatalkan '.$note->number.': '.$data['reason']);
            if ($note->issued_at) {
                $this->notifyWarehouse($note, 'cancelled');
            }
        });

        return back()->with('success', 'Nota dibatalkan. Request dikembalikan ke antrean pengadaan.');
    }

    public function print(ProcurementNote $procurementNote)
    {
        $this->recordPrint($procurementNote);
        $procurementNote->load(['items', 'creator']);

        return view('hr.procurement-notes.print', compact('procurementNote'));
    }

    public function excel(ProcurementNote $procurementNote)
    {
        abort_unless($procurementNote->issued_at, 422, 'Terbitkan nota sebelum mengekspor.');
        $procurementNote->load('items');
        $rows = $procurementNote->items->values()->map(fn ($item, $index) => [
            $index + 1,
            $item->item_name,
            $item->quantity,
            $item->received_quantity,
            $item->unit,
            $item->priority,
            $item->requester_name,
            $item->request_status,
            $item->review_note,
        ])->all();
        $export = new ProcurementNoteExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.strtolower($procurementNote->number).'.xlsx"');
    }

    private function validateNote(Request $request): array
    {
        return $request->validate([
            'request_ids' => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'distinct', 'exists:stock_requests,id'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function lockAvailableRequests(array $ids, ?int $noteId = null)
    {
        return StockRequest::with(['item', 'user'])->lockForUpdate()
            ->whereIn('id', $ids)
            ->where('status', 'Disetujui')
            ->where(function ($query) use ($noteId) {
                $query->whereNull('procurement_note_id');
                if ($noteId) $query->orWhere('procurement_note_id', $noteId);
            })->orderBy('id')->get();
    }

    private function replaceItems(ProcurementNote $note, $requests): void
    {
        foreach ($requests->values() as $index => $request) {
            $note->items()->create([
                'stock_request_id' => $request->id,
                'item_id' => $request->item_id,
                'item_name' => $request->item?->name ?? $request->item_name ?? 'Barang',
                'quantity' => $request->quantity,
                'received_quantity' => $request->received_quantity,
                'unit' => $request->unit,
                'priority' => $request->priority,
                'requester_name' => $request->user?->name,
                'review_note' => $request->review_note,
                'request_status' => $request->status,
                'sort_order' => $index,
            ]);
            $request->update(['procurement_note_id' => $note->id]);
        }
    }

    private function recordPrint(ProcurementNote $note): void
    {
        abort_unless($note->issued_at, 422, 'Terbitkan nota sebelum mencetak.');
        $note->update(['last_printed_at' => now()]);
        $this->audit('procurement_note_printed', $note, 'Mencetak '.$note->number);
    }

    private function nextNumber(): string
    {
        $date = now()->toDateString();
        DB::table('procurement_note_sequences')->insertOrIgnore(['date' => $date, 'last_number' => 0]);
        $sequence = DB::table('procurement_note_sequences')->where('date', $date)->lockForUpdate()->first();
        $next = $sequence->last_number + 1;
        DB::table('procurement_note_sequences')->where('date', $date)->update(['last_number' => $next]);

        return 'NOTA-'.now()->format('Ymd').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function audit(string $action, ProcurementNote $note, string $details): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => ProcurementNote::class,
            'target_id' => $note->id,
            'details' => $details,
        ]);
    }

    private function notifyWarehouse(ProcurementNote $note, string $event): void
    {
        $recipients = User::where('role', 'gudang')->where('is_active', true)->get();
        Notification::send($recipients, new ProcurementNoteNotification($note, $event));
    }
}
