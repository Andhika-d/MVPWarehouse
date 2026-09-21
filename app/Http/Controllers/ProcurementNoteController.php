<?php

namespace App\Http\Controllers;

use App\Exports\ProcurementNoteExport;
use App\Models\ProcurementNote;
use App\Models\StockRequest;
use App\Support\PeriodRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProcurementNoteController extends Controller
{
    public function index(Request $request)
    {
        $period = PeriodRange::fromRequest($request);
        $base = $this->filteredNotesQuery($request, $period);

        $notes = (clone $base)
            ->with(['requests.item', 'requests.user'])
            ->withCount('requests')
            ->latest('request_date')
            ->paginate(12)
            ->withQueryString();

        $activeBuilder = (clone $base)->whereHas(
            'requests',
            fn (Builder $requestQuery) => $requestQuery->whereNotIn('status', ProcurementNote::TERMINAL_REQUEST_STATUSES),
        );
        $totalNotes = (clone $base)->count();
        $activeCount = $activeBuilder->count();
        $requestCount = (clone $base)->withCount('requests')->get()->sum('requests_count');

        return view('procurement.index', [
            'notes' => $notes,
            'period' => $period,
            'totalNotes' => $totalNotes,
            'activeCount' => $activeCount,
            'completedCount' => $totalNotes - $activeCount,
            'requestCount' => $requestCount,
        ]);
    }

    public function show(ProcurementNote $procurementNote)
    {
        $procurementNote->load([
            'requests.item.storageLocation',
            'requests.user',
            'requests.reviewedBy',
            'requests.closedBy',
            'requests.requestHistories.user',
        ]);

        return view('procurement.show', compact('procurementNote'));
    }

    public function print(ProcurementNote $procurementNote)
    {
        $procurementNote->load($this->printRelations());
        $procurementNote->update(['last_printed_at' => now()]);

        return view('procurement.print', [
            'notes' => collect([$procurementNote]),
            'title' => 'Nota Pengadaan '.$procurementNote->number,
            'periodLabel' => $procurementNote->request_date->translatedFormat('d F Y'),
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => $this->roleLabel(Auth::user()->role),
        ]);
    }

    public function excel(ProcurementNote $procurementNote)
    {
        $procurementNote->load($this->printRelations());

        return $this->downloadExcel(
            collect([$procurementNote]),
            strtolower($procurementNote->number).'.xlsx',
        );
    }

    public function printPeriod(Request $request)
    {
        $period = PeriodRange::fromRequest($request);
        $notes = $this->filteredNotesQuery($request, $period)
            ->with($this->printRelations())
            ->latest('request_date')
            ->get();

        abort_if($notes->isEmpty(), 404);

        return view('procurement.print', [
            'notes' => $notes,
            'title' => 'Rekap Riwayat Pengadaan Barang',
            'periodLabel' => $period?->label() ?? 'Semua Periode',
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => $this->roleLabel(Auth::user()->role),
        ]);
    }

    public function excelPeriod(Request $request)
    {
        $period = PeriodRange::fromRequest($request);
        $notes = $this->filteredNotesQuery($request, $period)
            ->with($this->printRelations())
            ->latest('request_date')
            ->get();

        abort_if($notes->isEmpty(), 404);

        return $this->downloadExcel($notes, 'riwayat-pengadaan.xlsx');
    }

    private function filteredNotesQuery(Request $request, ?PeriodRange $period): Builder
    {
        $query = ProcurementNote::query();

        if ($period?->startDate()) {
            $query->whereDate('request_date', '>=', $period->startDate());
        }
        if ($period?->endDate()) {
            $query->whereDate('request_date', '<=', $period->endDate());
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $noteQuery) use ($search) {
                $noteQuery->where('number', 'like', "%{$search}%")
                    ->orWhereHas('requests', function (Builder $requestQuery) use ($search) {
                        $requestQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        if ($request->filled('request_status') && $request->input('request_status') !== 'all') {
            $status = $request->input('request_status');
            $query->whereHas('requests', fn (Builder $requestQuery) => $requestQuery->where('status', $status));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $priority = $request->input('priority');
            $query->whereHas('requests', fn (Builder $requestQuery) => $requestQuery->where('priority', $priority));
        }

        if ($request->input('note_status') === ProcurementNote::STATUS_COMPLETED) {
            $query->whereDoesntHave('requests', fn (Builder $requestQuery) => $requestQuery->whereNotIn('status', ProcurementNote::TERMINAL_REQUEST_STATUSES));
        } elseif ($request->input('note_status') === ProcurementNote::STATUS_ACTIVE) {
            $query->whereHas('requests', fn (Builder $requestQuery) => $requestQuery->whereNotIn('status', ProcurementNote::TERMINAL_REQUEST_STATUSES));
        }

        return $query;
    }

    private function printRelations(): array
    {
        return [
            'requests.item',
            'requests.user',
            'requests.reviewedBy',
            'requests.closedBy',
            'requests.requestHistories',
        ];
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'admin' => 'ADMIN',
            'hr' => 'HR',
            'director' => 'DIREKTUR',
            'gudang' => 'GUDANG',
            default => strtoupper((string) $role),
        };
    }

    private function downloadExcel($notes, string $filename)
    {
        $rows = $notes->flatMap(fn (ProcurementNote $note) => $note->requests->map(
            fn (StockRequest $stockRequest) => $this->exportRow($note, $stockRequest),
        ))->all();

        $export = new ProcurementNoteExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    private function exportRow(ProcurementNote $note, StockRequest $request): array
    {
        $decisionHistory = $request->requestHistories
            ->whereIn('status', ['Disetujui', 'Ditolak', 'Pending'])
            ->sortByDesc('created_at')
            ->first();
        $closedQuantity = in_array($request->status, StockRequest::CLOSED_STATUSES, true)
            ? max(0, $request->quantity - $request->received_quantity)
            : 0;
        $activeRemaining = $request->canReceive() ? max(0, $request->quantity - $request->received_quantity) : 0;

        return [
            $note->number,
            $request->id,
            $request->created_at?->translatedFormat('d M Y, H:i'),
            $request->item?->name ?? $request->item_name ?? 'Barang',
            $request->user?->name ?? '—',
            $request->quantity,
            $request->unit,
            $request->priority,
            $request->status,
            ($request->approved_at ?? $decisionHistory?->created_at)?->translatedFormat('d M Y, H:i'),
            $request->received_quantity,
            $closedQuantity,
            $activeRemaining,
            ($request->completed_at ?? $request->closed_at)?->translatedFormat('d M Y, H:i'),
            $request->review_note,
            $request->close_note,
            $request->closedBy?->name,
        ];
    }
}
