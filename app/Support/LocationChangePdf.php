<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

final class LocationChangePdf
{
    public static function render(
        Request $request,
        string $filename,
        array $rows,
        string $printedBy,
        string $printedRole,
        string $orientation = 'landscape'
    ) {
        $filters = [
            'Status' => $request->filled('status') && $request->input('status') !== 'all'
                ? $request->input('status')
                : 'Semua',
        ];

        PdfFonts::register();

        $pdf = Pdf::loadView('exports.location-changes', [
            'title' => 'Riwayat Pengajuan Lokasi',
            'rows' => $rows,
            'orientation' => $orientation,
            'periodLabel' => PeriodRange::fromRequest($request)?->label() ?? 'Semua Periode',
            'filters' => $filters,
            'printedAt' => now(),
            'printedBy' => $printedBy,
            'printedRole' => $printedRole,
        ])->setPaper('a4', $orientation);

        PdfFooter::apply($pdf);

        return $pdf->download($filename);
    }
}
