<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    public function store(Request $request, Item $item)
    {
        $data = $request->validate([
            'unit_name' => ['required', 'string', 'max:50'],
            'conversion_to_primary' => ['required', 'numeric', 'min:0.0001'],
            'context' => ['required', 'in:purchase,usage,both'],
        ]);

        // Check if unit already exists
        $exists = $item->itemUnits()->where('unit_name', $data['unit_name'])->exists();
        if ($exists) {
            return response()->json(['message' => __('Unit sudah ada')], 422);
        }

        $item->itemUnits()->create([
            'unit_name' => $data['unit_name'],
            'conversion_to_primary' => $data['conversion_to_primary'],
            'is_primary' => false,
            'context' => $data['context'],
        ]);

        return response()->json(['message' => __('Unit berhasil ditambahkan')]);
    }

    public function destroy(Item $item, string $unitName)
    {
        $unit = $item->itemUnits()->where('unit_name', $unitName)->first();
        
        if (!$unit) {
            return response()->json(['message' => __('Unit tidak ditemukan')], 404);
        }

        if ($unit->is_primary) {
            return response()->json(['message' => __('Tidak dapat menghapus unit dasar')], 422);
        }

        $unit->delete();

        return response()->json(['message' => __('Unit berhasil dihapus')]);
    }
}
