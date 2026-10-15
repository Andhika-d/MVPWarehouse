<?php

namespace App\Http\Controllers\AdminCoil;

use App\Exports\CoilTagExport;
use App\Http\Controllers\Controller;
use App\Models\CoilTag;
use App\Support\PdfFonts;
use App\Support\PdfFooter;
use App\Support\PrintOrientation;
use App\Support\RoleLabel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CoilTagController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters);
        $coilTags = (clone $query)->paginate(25)->withQueryString();
        $totalWeight = number_format((float) (clone $query)->sum('berat'), 3, '.', '');

        return view('admin-coil.coil-tags.index', [
            'coilTags' => $coilTags,
            'totalQuantity' => $coilTags->total(),
            'totalWeight' => $totalWeight,
            'filters' => $filters,
            'filterDescription' => $this->filterDescription($filters),
        ]);
    }

    public function show(CoilTag $coilTag): View
    {
        return view('admin-coil.coil-tags.show', ['coilTag' => $coilTag]);
    }

    public function print(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $orientation = PrintOrientation::resolve($request, $filters['orientation']);
        $coilTags = $this->filteredQuery($filters)->get();

        return view('exports.coil-tags', [
            'coilTags' => $coilTags,
            'title' => 'LAPORAN TAEHANG / COIL TAGGING',
            'orientation' => $orientation,
            'periodLabel' => $this->periodLabel($filters),
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
        $orientation = PrintOrientation::resolve($request, $filters['orientation']);
        $coilTags = $this->filteredQuery($filters)->get();

        PdfFonts::register();

        $pdf = Pdf::loadView('exports.coil-tags', [
            'coilTags' => $coilTags,
            'title' => 'LAPORAN TAEHANG / COIL TAGGING',
            'orientation' => $orientation,
            'periodLabel' => $this->periodLabel($filters),
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
        $rows = $this->filteredQuery($filters)->get()
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
     * @return array{search: string, date_filter: string, date_from: ?string, date_to: ?string, location: string, orientation: string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'date_filter' => ['nullable', 'in:all,today,custom'],
            'date_from' => ['nullable', 'required_if:date_filter,custom', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_if:date_filter,custom', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'location' => ['nullable', 'in:AL,AR,BL,BR,CL,CR'],
            'orientation' => ['nullable', 'in:landscape,portrait'],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'date_filter' => $validated['date_filter'] ?? 'all',
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'location' => $validated['location'] ?? '',
            'orientation' => $validated['orientation'] ?? 'landscape',
        ];
    }

    /**
     * @param  array{search: string, date_filter: string, date_from: ?string, date_to: ?string, location: string, orientation: string}  $filters
     * @return Builder<CoilTag>
     */
    private function filteredQuery(array $filters): Builder
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

        if ($filters['date_filter'] === 'today') {
            $query->whereDate('scanned_at', now()->toDateString());
        } elseif ($filters['date_filter'] === 'custom') {
            $query->whereDate('scanned_at', '>=', $filters['date_from'])
                ->whereDate('scanned_at', '<=', $filters['date_to']);
        }

        if ($filters['location'] !== '') {
            $query->where('lokasi_coil', $filters['location']);
        }

        return $query->orderByDesc('scanned_at')->orderByDesc('id');
    }

    /**
     * @param  array{search: string, date_filter: string, date_from: ?string, date_to: ?string, location: string, orientation: string}  $filters
     */
    private function filterDescription(array $filters): string
    {
        $dateDescription = match ($filters['date_filter']) {
            'today' => __('Hari ini').' ('.now()->toDateString().')',
            'custom' => $filters['date_from'].' s/d '.$filters['date_to'],
            default => __('Semua tanggal'),
        };

        $locationDescription = $filters['location'] !== '' ? __('Lokasi').': '.$filters['location'].' | ' : '';

        return $locationDescription.($filters['search'] !== '' ? __('Pencarian').': '.$filters['search'].' | ' : '').$dateDescription;
    }

    /**
     * @param  array{search: string, date_filter: string, date_from: ?string, date_to: ?string, location: string, orientation: string}  $filters
     */
    private function periodLabel(array $filters): string
    {
        return match ($filters['date_filter']) {
            'today' => now()->translatedFormat('d F Y'),
            'custom' => $filters['date_from'].' - '.$filters['date_to'],
            default => __('Semua Periode'),
        };
    }

    /**
     * @param  array{search: string, date_filter: string, date_from: ?string, date_to: ?string, location: string, orientation: string}  $filters
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
