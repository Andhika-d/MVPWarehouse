<?php

namespace App\Http\Controllers\AdminCoil;

use App\Exports\CoilTagExport;
use App\Http\Controllers\Controller;
use App\Models\CoilTag;
use App\Support\PdfFonts;
use App\Support\PdfFooter;
use App\Support\PeriodRange;
use App\Support\PrintOrientation;
use App\Support\RoleLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CoilTagController extends Controller
{
    /** @var list<string> */
    private const LOCATIONS = ['AL', 'AR', 'BL', 'BR', 'CL', 'CR'];

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $period = PeriodRange::fromRequest($request);
        $coilTags = $this->filteredQuery($filters, $period)->paginate(25)->withQueryString();

        return view('admin-coil.coil-tags.index', [
            'coilTags' => $coilTags,
            'summary' => $this->summary(),
            'locationData' => $this->locationData(),
            'filters' => $filters,
            'period' => $period,
            'filterDescription' => $this->filterDescription($filters, $period),
        ]);
    }

    public function show(CoilTag $coilTag): View
    {
        return view('admin-coil.coil-tags.show', ['coilTag' => $coilTag]);
    }

    public function print(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $period = PeriodRange::fromRequest($request);
        $orientation = PrintOrientation::resolve($request, $filters['orientation']);
        $coilTags = $this->filteredQuery($filters, $period)->get();

        return view('exports.coil-tags', [
            'coilTags' => $coilTags,
            'title' => 'LAPORAN TAEHANG / COIL TAGGING',
            'orientation' => $orientation,
            'periodLabel' => $this->periodLabel($period),
            'filters' => $this->exportFilters($filters),
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => RoleLabel::of(Auth::user()->role),
            'showToolbar' => true,
            'backUrl' => url('/admin-coil/coil-tags'),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $period = PeriodRange::fromRequest($request);
        $orientation = PrintOrientation::resolve($request, $filters['orientation']);
        $coilTags = $this->filteredQuery($filters, $period)->get();

        PdfFonts::register();

        $pdf = Pdf::loadView('exports.coil-tags', [
            'coilTags' => $coilTags,
            'title' => 'LAPORAN TAEHANG / COIL TAGGING',
            'orientation' => $orientation,
            'periodLabel' => $this->periodLabel($period),
            'filters' => $this->exportFilters($filters),
            'printedAt' => now(),
            'printedBy' => Auth::user()->name,
            'printedRole' => RoleLabel::of(Auth::user()->role),
            'showToolbar' => false,
            'backUrl' => null,
        ])->setPaper('a4', $orientation);

        PdfFooter::apply($pdf);

        return $pdf->download('laporan-coil-tag.pdf');
    }

    public function export(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $period = PeriodRange::fromRequest($request);
        $rows = $this->filteredQuery($filters, $period)->get()
            ->values()
            ->map(fn (CoilTag $coilTag, int $index): array => [
                $index + 1,
                $coilTag->company ?: '-',
                $coilTag->tag_taehang,
                $coilTag->nomor_coil,
                $coilTag->grade,
                rtrim(rtrim((string) $coilTag->tebal, '0'), '.'),
                rtrim(rtrim((string) $coilTag->lebar, '0'), '.'),
                $coilTag->beratAsScanned(),
                1,
                $coilTag->lokasi_coil ?: '-',
                $coilTag->scanned_at?->format('Y-m-d H:i'),
            ])
            ->all();

        $export = new CoilTagExport($rows);

        return response($export->toXlsx(), 200)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="laporan-coil-tag.xlsx"');
    }

    /**
     * @return array{search: string, location: string, orientation: string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', Rule::in(self::LOCATIONS)],
            'orientation' => ['nullable', 'in:landscape,portrait'],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'location' => $validated['location'] ?? '',
            'orientation' => $validated['orientation'] ?? 'landscape',
        ];
    }

    /**
     * @param  array{search: string, location: string, orientation: string}  $filters
     * @return Builder<CoilTag>
     */
    private function filteredQuery(array $filters, ?PeriodRange $period): Builder
    {
        $query = CoilTag::query();

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('nomor_coil', 'like', $search)
                    ->orWhere('tag_taehang', 'like', $search)
                    ->orWhere('grade', 'like', $search)
                    ->orWhere('lokasi_coil', 'like', $search);
            });
        }

        if ($filters['location'] !== '') {
            $query->where('lokasi_coil', $filters['location']);
        }

        $period?->apply($query, 'scanned_at');

        return $query->orderByDesc('scanned_at')->orderByDesc('id');
    }

    /**
     * @param  array{search: string, location: string, orientation: string}  $filters
     */
    private function filterDescription(array $filters, ?PeriodRange $period): string
    {
        $parts = [];

        if ($filters['location'] !== '') {
            $parts[] = __('Lokasi').': '.$filters['location'];
        }

        if ($filters['search'] !== '') {
            $parts[] = __('Pencarian').': '.$filters['search'];
        }

        $parts[] = $period?->label() ?? __('Semua Periode');

        return implode(' | ', $parts);
    }

    private function periodLabel(?PeriodRange $period): string
    {
        return $period?->label() ?? __('Semua Periode');
    }

    /**
     * @return array{total_qty: int, total_weight: float, today: int, locations: int}
     */
    private function summary(): array
    {
        return [
            'total_qty' => CoilTag::count(),
            'total_weight' => (float) CoilTag::sum('berat'),
            'today' => CoilTag::whereDate('scanned_at', now()->toDateString())->count(),
            'locations' => CoilTag::whereNotNull('lokasi_coil')->distinct()->count('lokasi_coil'),
        ];
    }

    /**
     * @return Collection<int, array{location: string, total: int}>
     */
    private function locationData(): Collection
    {
        $counts = CoilTag::query()
            ->whereIn('lokasi_coil', self::LOCATIONS)
            ->selectRaw('lokasi_coil, COUNT(*) as total')
            ->groupBy('lokasi_coil')
            ->pluck('total', 'lokasi_coil');

        return collect(self::LOCATIONS)->map(fn (string $location): array => [
            'location' => $location,
            'total' => (int) ($counts[$location] ?? 0),
        ]);
    }

    /**
     * @param  array{search: string, location: string, orientation: string}  $filters
     * @return array<string, string>
     */
    private function exportFilters(array $filters): array
    {
        return [
            __('Pencarian') => $filters['search'] !== '' ? $filters['search'] : __('Semua'),
            __('Lokasi') => $filters['location'] !== '' ? $filters['location'] : __('Semua'),
        ];
    }
}
