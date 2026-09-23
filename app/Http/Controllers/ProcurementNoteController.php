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

        $search = trim((string) $request->input('search', ''));
        $searchActive = $search !== '';
        $matchingRequestsByNote = $searchActive
            ? $notes->getCollection()->mapWithKeys(function (ProcurementNote $note) use ($request, $search) {
                $matched = $note->requests->filter(function (StockRequest $stockRequest) use ($request, $search) {
                    return $this->requestMatchesSearch($stockRequest, $search)
                        && $this->requestMatchesFilters($stockRequest, $request);
                })->values();

                return [$note->id => $matched];
            })
            : collect();

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
            'searchActive' => $searchActive,
            'search' => $search,
            'matchingRequestsByNote' => $matchingRequestsByNote,
        ]);
    }

    public function show(ProcurementNote $procurementNote)
    {
        $procurementNote->load([
            'requests.item.storageLocation',
            'requests.user',
            'requests.reviewedBy',
            'requests.closedBy',
        ]);

        return view('procurement.show', compact('procurementNote'));
    }

    public function requestDetail(ProcurementNote $procurementNote, StockRequest $request)
    {
        abort_unless($request->procurement_note_id === $procurementNote->id, 404);

        $request->load([
            'item.storageLocation',
            'user',
            'reviewedBy',
            'closedBy',
            'requestHistories.user',
        ]);

        $timeline = $request->requestHistories
            ->sortBy('created_at')
            ->map(fn ($history) => [
                'status' => $history->status,
                'user' => $history->user?->name ?? 'Sistem',
                'time' => $history->created_at,
                'note' => $history->note,
            ]);

        if (! $timeline->contains('status', 'Menunggu Review')) {
            $timeline->push([
                'status' => 'Menunggu Review',
                'user' => $request->user?->name ?? '—',
                'time' => $request->created_at,
                'note' => 'Barang: '.($request->item?->name ?? $request->item_name ?? 'Barang').' — '.$request->quantity.' '.$request->unit,
            ]);
        }

        $timeline = $timeline->sortBy(fn ($event) => $event['time'])->values();

        return view('procurement.request-detail', compact('procurementNote', 'request', 'timeline'));
    }

    public function print(Request $httpRequest, ProcurementNote $procurementNote)
    {
        $orientation = $this->printOrientation($httpRequest);
        $procurementNote->load($this->printRelations());
        $procurementNote->update(['last_printed_at' => now()]);

        return view('procurement.print', [
            'notes' => collect([$procurementNote]),
            'title' => 'Nota Pengadaan '.$procurementNote->number,
            'periodLabel' => $procurementNote->request_date->translatedFormat('d F Y'),
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => $this->roleLabel(Auth::user()->role),
            'orientation' => $orientation,
            'backUrl' => route('procurement-notes.show', $procurementNote),
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
        $orientation = $this->printOrientation($request);
        $period = PeriodRange::fromRequest($request);
        $notes = $this->filteredNotesQuery($request, $period)
            ->with($this->printRelations())
            ->latest('request_date')
            ->get();

        abort_if($notes->isEmpty(), 404);

        $backQuery = $request->except('orientation');

        return view('procurement.print', [
            'notes' => $notes,
            'title' => 'Rekap Riwayat Pengadaan Barang',
            'periodLabel' => $period?->label() ?? 'Semua Periode',
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => $this->roleLabel(Auth::user()->role),
            'orientation' => $orientation,
            'backUrl' => route('procurement-notes.index').($backQuery ? '?'.http_build_query($backQuery) : ''),
        ]);
    }

    private function printOrientation(Request $request): string
    {
        $validated = $request->validate([
            'orientation' => ['nullable', 'in:landscape,portrait'],
        ]);

        return $validated['orientation'] ?? 'landscape';
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

        $search = trim((string) $request->input('search', ''));
        $status = $request->filled('request_status') && $request->input('request_status') !== 'all'
            ? $request->input('request_status')
            : null;
        $priority = $request->filled('priority') && $request->input('priority') !== 'all'
            ? $request->input('priority')
            : null;

        if ($search !== '' || $status !== null || $priority !== null) {
            $query->where(function (Builder $noteQuery) use ($search, $status, $priority) {
                if ($search !== '') {
                    $pattern = $this->likePattern($search);
                    $noteQuery->whereRaw("number LIKE ? ESCAPE '\\'", [$pattern]);
                }

                $noteQuery->orWhereHas('requests', function (Builder $requestQuery) use ($search, $status, $priority) {
                    if ($search !== '') {
                        $pattern = $this->likePattern($search);
                        $requestQuery->where(function (Builder $valueQuery) use ($pattern) {
                            $valueQuery->whereRaw("item_name LIKE ? ESCAPE '\\'", [$pattern])
                                ->orWhereHas('item', fn (Builder $itemQuery) => $itemQuery->whereRaw("name LIKE ? ESCAPE '\\'", [$pattern]))
                                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->whereRaw("name LIKE ? ESCAPE '\\'", [$pattern]));
                        });
                    }

                    if ($status !== null) {
                        $requestQuery->where('status', $status);
                    }

                    if ($priority !== null) {
                        $requestQuery->where('priority', $priority);
                    }
                });
            });
        }

        if ($request->input('note_status') === ProcurementNote::STATUS_COMPLETED) {
            $query->whereDoesntHave('requests', fn (Builder $requestQuery) => $requestQuery->whereNotIn('status', ProcurementNote::TERMINAL_REQUEST_STATUSES));
        } elseif ($request->input('note_status') === ProcurementNote::STATUS_ACTIVE) {
            $query->whereHas('requests', fn (Builder $requestQuery) => $requestQuery->whereNotIn('status', ProcurementNote::TERMINAL_REQUEST_STATUSES));
        }

        return $query;
    }

    private function requestMatchesSearch(StockRequest $stockRequest, string $search): bool
    {
        $searchable = array_filter([
            $stockRequest->item?->name,
            $stockRequest->item_name,
            $stockRequest->user?->name,
        ]);

        foreach ($searchable as $value) {
            if (is_string($value) && mb_stripos($value, $search) !== false) {
                return true;
            }
        }

        return false;
    }

    private function likePattern(string $term): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);

        return "%{$escaped}%";
    }

    private function requestMatchesFilters(StockRequest $stockRequest, Request $request): bool
    {
        if ($request->filled('request_status') && $request->input('request_status') !== 'all'
            && $stockRequest->status !== $request->input('request_status')) {
            return false;
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all'
            && $stockRequest->priority !== $request->input('priority')) {
            return false;
        }

        return true;
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
