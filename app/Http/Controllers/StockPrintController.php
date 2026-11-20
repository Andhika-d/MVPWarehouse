<?php

namespace App\Http\Controllers;

use App\Models\Item;
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
            'stock' => round((float) $items->sum('stock'), 2),
            'low_stock' => $items->where('stock', '>', 0)->where('stock', '<=', Item::LOW_STOCK_THRESHOLD)->count(),
            'out_of_stock' => $items->where('stock', 0)->count(),
        ];
        $filters = [
            'Pencarian' => $request->query('search') ?: 'Semua',
            'Rak' => $request->query('rack') ?: 'Semua',
            'Status' => $statusLabels[$request->query('status')] ?? 'Semua',
        ];
        if ($request->boolean('low_stock')) {
            $filters['Stok Menipis'] = 'Ya';
        }
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
