<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    /**
     * Get available units for an item based on context
     */
    public function getUnits(Request $request, Item $item)
    {
        $context = $request->input('context', 'both'); // purchase, usage, both
        
        $query = $item->itemUnits();
        
        if ($context !== 'both') {
            $query->where(function ($q) use ($context) {
                $q->where('context', $context)
                  ->orWhere('context', 'both');
            });
        }
        
        $units = $query->get()->map(function ($unit) use ($item) {
            return [
                'name' => $unit->unit_name,
                'conversion' => $unit->conversion_to_primary,
                'is_primary' => $unit->is_primary,
                'conversion_text' => $unit->is_primary 
                    ? null 
                    : "1 {$unit->unit_name} = {$unit->conversion_to_primary} {$item->primary_unit}",
            ];
        });
        
        return response()->json([
            'units' => $units,
            'primary_unit' => $item->primary_unit,
            'current_stock' => $item->stock,
            'stock_display' => $item->stock_display,
        ]);
    }
}
