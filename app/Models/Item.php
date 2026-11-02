<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    public const UNITS = ['Pcs', 'Pck', 'Box', 'Kg', 'Roll', 'Rim', 'Set'];

    protected $fillable = [
        'name',
        'size',
        'storage_location_id',
        'stock',
        'unit',
        'primary_unit',
    ];

    protected $casts = [
        'storage_location_id' => 'integer',
    ];

    public function stockRequests()
    {
        return $this->hasMany(StockRequest::class);
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function itemUnits()
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get conversion factor for a given unit
     */
    public function getConversionFactor(string $unit): float
    {
        if ($unit === $this->primary_unit) {
            return 1.0;
        }

        $itemUnit = $this->itemUnits()->where('unit_name', $unit)->first();
        
        return $itemUnit ? $itemUnit->conversion_to_primary : 1.0;
    }

    /**
     * Convert quantity to primary unit
     */
    public function convertToPrimary(float $quantity, string $unit): float
    {
        return $quantity * $this->getConversionFactor($unit);
    }

    /**
     * Display stock with breakdown
     */
    public function getStockDisplayAttribute(): string
    {
        $stock = $this->stock;
        $primaryUnit = $this->primary_unit ?? $this->unit;
        
        // Get alternative units sorted by conversion (largest first)
        $units = $this->itemUnits()
            ->where('is_primary', false)
            ->orderByDesc('conversion_to_primary')
            ->get();
        
        if ($units->isEmpty()) {
            return "{$stock} {$primaryUnit}";
        }
        
        $breakdown = [];
        $remaining = $stock;
        
        foreach ($units as $unit) {
            $count = floor($remaining / $unit->conversion_to_primary);
            if ($count > 0) {
                $breakdown[] = "{$count} {$unit->unit_name}";
                $remaining = fmod($remaining, $unit->conversion_to_primary);
            }
        }
        
        if ($remaining > 0) {
            $breakdown[] = "{$remaining} {$primaryUnit}";
        }
        
        $breakdownStr = !empty($breakdown) ? ' (' . implode(' + ', $breakdown) . ')' : '';
        
        return "{$stock} {$primaryUnit}{$breakdownStr}";
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim($this->name);

        return $this->size ? $name . ' (' . trim($this->size) . ')' : $name;
    }

    public function getLocationCodeAttribute(): ?string
    {
        return $this->storageLocation?->code;
    }
}
