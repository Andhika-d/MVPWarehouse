<?php

namespace App\Http\Controllers\AdminCoil;

use App\Http\Controllers\Controller;
use App\Models\CoilLocation;
use App\Models\CoilTag;
use App\Models\CoilTagLocationHistory;
use App\Support\PeriodRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoilMovementController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', Rule::in(CoilLocation::filterOptions())],
            'type' => ['nullable', Rule::in(['scan', 'lokasi'])],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $location = (string) ($validated['location'] ?? '');
        $type = (string) ($validated['type'] ?? '');
        $period = PeriodRange::fromRequest($request);

        $scan = $this->scanQuery($search, $location);
        $period?->apply($scan, 'coil_tags.scanned_at');

        $history = $this->historyQuery($search, $location);
        $period?->apply($history, 'coil_tag_location_histories.created_at');

        $query = match ($type) {
            'scan' => $scan,
            'lokasi' => $history,
            default => $scan->unionAll($history),
        };

        $events = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('sort_key')
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
        ]);
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
