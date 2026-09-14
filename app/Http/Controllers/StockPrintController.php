<?php

namespace App\Http\Controllers;

use App\Support\StockLocationQuery;
use Illuminate\Http\Request;

class StockPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $locations = StockLocationQuery::fromRequest($request)->get();
        $items = $locations->flatMap->items;
        $statusLabels = [
            'occupied' => 'Terisi',
            'empty' => 'Lokasi Kosong',
            'item_empty' => 'Barang Kosong',
        ];
        $roleLabels = [
            'gudang' => 'Gudang',
            'hr' => 'HR',
            'director' => 'Direktur',
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
        $backUrl .= $request->getQueryString() ? '?'.$request->getQueryString() : '';

        return view('stock.print', [
            'locations' => $locations,
            'summary' => $summary,
            'filters' => $filters,
            'printedAt' => now(),
            'printedBy' => $request->user()->name,
            'role' => $roleLabels[$request->user()->role] ?? ucfirst($request->user()->role),
            'backUrl' => $backUrl,
        ]);
    }
}
