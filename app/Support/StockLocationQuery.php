<?php

namespace App\Support;

use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockLocationQuery
{
    public static function fromRequest(Request $request): Builder
    {
        $query = StorageLocation::with('items');
        $rack = $request->query('rack');
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        if ($rack && $rack !== 'all') {
            $query->where('rack', $rack);
        }

        if ($search !== '') {
            $query->where(function ($filter) use ($search) {
                $filter->where('code', 'like', "%{$search}%")
                    ->orWhere('sub_location', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($items) use ($search) {
                        $items->where('name', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'occupied') {
            $query->where('status', StorageLocation::STATUS_OCCUPIED);
        } elseif ($status === 'empty') {
            $query->where('status', StorageLocation::STATUS_EMPTY);
        } elseif ($status === 'item_empty') {
            $query->whereHas('items', fn ($items) => $items->where('stock', 0));
        }

        return $query->orderBy('sub_location')->orderBy('number');
    }
}
