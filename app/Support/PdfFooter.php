<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;

final class PdfFooter
{
    public static function apply(PDF $pdf): void
    {
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics): void {
            if ($pageCount <= 1) {
                return;
            }

            $text = 'Halaman '.$pageNumber.' dari '.$pageCount;
            $size = 8;
            $font = $fontMetrics->get_font('Arial');
            $width = $canvas->get_text_width($text, $font, $size);
            $x = ($canvas->get_width() - $width) / 2;
            $y = $canvas->get_height() - 20;
            $color = array_map(fn (int $channel): float => $channel / 255, [100, 116, 139]);

            $canvas->text($x, $y, $text, $font, $size, $color);
        });
    }
}
