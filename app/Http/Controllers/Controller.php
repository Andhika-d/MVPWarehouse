<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

abstract class Controller
{
    protected function exportPreview(
        string $title,
        array $columns,
        array $rows,
        string $backUrl,
        array $downloads
    ): View {
        return view('exports.preview', [
            'title' => $title,
            'columns' => $columns,
            'rows' => array_slice($rows, 0, 100),
            'totalRows' => count($rows),
            'backUrl' => $backUrl,
            'downloads' => $downloads,
        ]);
    }
}
