<?php

namespace App\Http\Controllers;

use App\Support\PrintOrientation;
use App\Support\RoleLabel;
use App\Support\StockLocationQuery;
use Illuminate\Http\Request;

class StockPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $orientation = PrintOrientation::resolve($request);
        $locations = StockLocationQuery::fromRequest($request)->get();
        $items = $locations->flatMap->items;
        $statusLabels = [
            'occupied' => 'Terisi',
            'empty' => 'Lokasi Kosong',
            'item_empty' => 'Barang Kosong',
        ];

        $summary = [
            'locations' => $locations->count(),
            'items' => $items->count(),
            'stock' => (int) $items->sum('stock'),
            'low_stock' => $items->where('stock', '>', 0)->where('stock', '<=', 5)->count(),
            'out_of_stock' => $items->where('stock', 0)->count(),
        ];
        $filters = [
            'Pencarian' => $request->query('search') ?: 'Semua',
            'Rak' => $request->query('rack') ?: 'Semua',
            'Status' => $statusLabels[$request->query('status')] ?? 'Semua',
        ];
        $backUrl = match ($request->user()->role) {
            'hr' => '/hr/stock',
            'director' => '/director/stock',
            default => '/gudang/stock',
        };
        $backQuery = $request->except('orientation');
        $backUrl .= $backQuery ? '?'.http_build_query($backQuery) : '';

        return view('stock.print', [
            'locations' => $locations,
            'summary' => $summary,
            'filters' => $filters,
            'printedAt' => now(),
            'printedBy' => $request->user()->name,
            'printedRole' => RoleLabel::of($request->user()->role),
            'backUrl' => $backUrl,
            'orientation' => $orientation,
        ]);
    }
}