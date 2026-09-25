<?php

namespace App\Support;

final class PdfFonts
{
    public static function register(): void
    {
        $dir = storage_path('fonts');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fontDir = resource_path('fonts');
        $fonts = [
            ['NanumGothic', 'normal', 'NanumGothic-Regular.ttf'],
            ['NanumGothic', 'bold', 'NanumGothic-Bold.ttf'],
        ];

        $metrics = app('dompdf')->getFontMetrics();

        foreach ($fonts as [$family, $weight, $file]) {
            $path = $fontDir.DIRECTORY_SEPARATOR.$file;
            if (! is_file($path)) {
                continue;
            }

            $metrics->registerFont(
                ['family' => $family, 'weight' => $weight, 'style' => 'normal'],
                'file://'.str_replace('\\', '/', $path)
            );
        }
    }
}
