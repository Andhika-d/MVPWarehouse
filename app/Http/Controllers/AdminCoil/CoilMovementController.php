<?php

namespace App\Http\Controllers\AdminCoil;

use App\Exports\CoilMovementExport;
use App\Http\Controllers\Controller;
use App\Models\CoilLocation;
use App\Models\CoilTag;
use App\Models\CoilTagLocationHistory;
use App\Support\PeriodRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CoilMovementController extends Controller
{
    public function index(Request $request): View
    {
        ['search' => $search, 'location' => $location, 'type' => $type, 'period' => $period] = $this->resolveFilters($request);

        $events = $this->eventsQuery($search, $location, $type, $period)
            ->paginate(25)
            ->withQueryString();

        return view('admin-coil.movements.index', [
            'events' => $events,
            'filters' => [
                'search' => $search,
                'location' => $location,
                'type' => $type,
            ],
            'locations' => $this->locationOptions(),
            'period' => $period,
            'counts' => $this->typeCounts($search, $location, $period),
        ]);
    }

    public function export(Request $request): Response
    {
        ['search' => $search, 'location' => $location, 'type' => $type, 'period' => $period] = $this->resolveFilters($request);

        $rows = $this->eventsQuery($search, $location, $type, $period)
            ->get()
            ->values()
            ->map(fn ($event, int $index): array => [
                $index + 1,
                $event->occurred_at ? Carbon::parse($event->occurred_at)->format('Y-m-d H:i') : '-',
                $event->event_type === 'scan' ? __('Scan') : __('Pindah Lokasi'),
                $event->nomor_coil,
                $event->company ?: '-',
                $event->grade ?: '-',
                $event->from_location ?: '-',
                $event->to_location ?: '-',
                $event->current_location ?: '-',
                $event->actor_name ?: '-',
                $this->sourceLabel((string) $event->source),
            ])
            ->all();

        $export = new CoilMovementExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="pergerakan-coil.xlsx"');
    }

    /**
     * @return array{search: string, location: string, type: string, period: ?PeriodRange}
     */
    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', Rule::in(CoilLocation::filterOptions())],
            'type' => ['nullable', Rule::in(['scan', 'lokasi'])],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'location' => (string) ($validated['location'] ?? ''),
            'type' => (string) ($validated['type'] ?? ''),
            'period' => PeriodRange::fromRequest($request),
        ];
    }

    private function eventsQuery(string $search, string $location, string $type, ?PeriodRange $period): Builder
    {
        $scan = $this->scanQuery($search, $location);
        $period?->apply($scan, 'coil_tags.scanned_at');

        $history = $this->historyQuery($search, $location);
        $period?->apply($history, 'coil_tag_location_histories.created_at');

        $query = match ($type) {
            'scan' => $scan,
            'lokasi' => $history,
            default => $scan->unionAll($history),
        };

        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('sort_key');
    }

    /**
     * Event totals per type, ignoring the type filter so tabs always show every count.
     *
     * @return array{all: int, scan: int, lokasi: int}
     */
    private function typeCounts(string $search, string $location, ?PeriodRange $period): array
    {
        $scan = CoilTag::query();
        $this->applyCoilFilters($scan, $search, $location, 'coil_tags.lokasi_coil');
        $period?->apply($scan, 'coil_tags.scanned_at');

        $history = CoilTagLocationHistory::query()
            ->join('coil_tags', 'coil_tags.id', '=', 'coil_tag_location_histories.coil_tag_id');
        $this->applyCoilFilters($history, $search, $location, 'coil_tag_location_histories.to_location');
        $period?->apply($history, 'coil_tag_location_histories.created_at');

        $scanCount = $scan->count();
        $historyCount = $history->count();

        return [
            'all' => $scanCount + $historyCount,
            'scan' => $scanCount,
            'lokasi' => $historyCount,
        ];
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            CoilTagLocationHistory::SOURCE_ADMIN_MANUAL => __('Admin Coil'),
            CoilTagLocationHistory::SOURCE_ADMIN_IMPORT => __('Import'),
            CoilTagLocationHistory::SOURCE_CHECKER => __('Checker'),
            default => $source,
        };
    }

    /**
     * Filterable locations for the form, with the human label for the sentinel.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function locationOptions(): array
    {
        return collect(CoilLocation::filterOptions())
            ->map(fn (string $kode): array => [
                'value' => $kode,
                'label' => $kode === CoilLocation::FILTER_NONE ? __('Tanpa Lokasi') : $kode,
            ])
            ->all();
    }

    private function scanQuery(string $search, string $location): Builder
    {
        $query = CoilTag::query()
            ->leftJoin('users as actor', 'actor.id', '=', 'coil_tags.user_id');

        $this->applyCoilFilters($query, $search, $location, 'coil_tags.lokasi_coil');

        return $query
            ->selectRaw("'scan' as event_type")
            ->selectRaw('coil_tags.id as coil_tag_id')
            ->selectRaw('coil_tags.scanned_at as occurred_at')
            ->selectRaw('coil_tags.nomor_coil as nomor_coil')
            ->selectRaw('coil_tags.tag_taehang as tag_taehang')
            ->selectRaw('coil_tags.company as company')
            ->selectRaw('coil_tags.grade as grade')
            ->selectRaw('coil_tags.lokasi_coil as current_location')
            ->selectRaw('coil_tags.raw_kp as raw_kp')
            ->selectRaw('coil_tags.raw_taehang as raw_taehang')
            ->selectRaw('null as from_location')
            ->selectRaw('null as to_location')
            ->selectRaw("'checker' as source")
            ->selectRaw('actor.name as actor_name')
            ->selectRaw('coil_tags.id as sort_key');
    }

    private function historyQuery(string $search, string $location): Builder
    {
        $query = CoilTagLocationHistory::query()
            ->join('coil_tags', 'coil_tags.id', '=', 'coil_tag_location_histories.coil_tag_id')
            ->leftJoin('users as actor', 'actor.id', '=', 'coil_tag_location_histories.changed_by');

        // A location movement belongs to its destination, so the location filter
        // matches the coil's location at the time of the event, not today's value.
        $this->applyCoilFilters($query, $search, $location, 'coil_tag_location_histories.to_location');

        return $query
            ->selectRaw("'lokasi' as event_type")
            ->selectRaw('coil_tag_location_histories.coil_tag_id as coil_tag_id')
            ->selectRaw('coil_tag_location_histories.created_at as occurred_at')
            ->selectRaw('coil_tags.nomor_coil as nomor_coil')
            ->selectRaw('coil_tags.tag_taehang as tag_taehang')
            ->selectRaw('coil_tags.company as company')
            ->selectRaw('coil_tags.grade as grade')
            ->selectRaw('coil_tags.lokasi_coil as current_location')
            ->selectRaw('null as raw_kp')
            ->selectRaw('null as raw_taehang')
            ->selectRaw('coil_tag_location_histories.from_location as from_location')
            ->selectRaw('coil_tag_location_histories.to_location as to_location')
            ->selectRaw('coil_tag_location_histories.source as source')
            ->selectRaw('actor.name as actor_name')
            ->selectRaw('coil_tag_location_histories.id as sort_key');
    }

    private function applyCoilFilters(Builder $query, string $search, string $location, string $locationColumn): void
    {
        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(function (Builder $nested) use ($like): void {
                $nested->where('coil_tags.nomor_coil', 'like', $like)
                    ->orWhere('coil_tags.tag_taehang', 'like', $like)
                    ->orWhere('coil_tags.company', 'like', $like)
                    ->orWhere('coil_tags.grade', 'like', $like)
                    ->orWhere('coil_tags.lokasi_coil', 'like', $like);
            });
        }

        if ($location === CoilLocation::FILTER_NONE) {
            $query->whereNull($locationColumn);
        } elseif ($location !== '') {
            $query->where($locationColumn, $location);
        }
    }
}
