<?php

namespace App\Models;

use App\Casts\NumericValue;
use Illuminate\Database\Eloquent\Model;

class ItemUnit extends Model
{
    protected $fillable = [
        'item_id',
        'unit_name',
        'conversion_to_primary',
        'is_primary',
        'context',
    ];

    protected $casts = [
        'conversion_to_primary' => NumericValue::class,
        'is_primary' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Convert quantity from this unit to primary unit
     */
    public function convertToPrimary(float $quantity): float
    {
        return $quantity * $this->conversion_to_primary;
    }

    /**
     * Convert quantity from primary unit to this unit
     */
    public function convertFromPrimary(float $quantityInPrimary): float
    {
        return $quantityInPrimary / $this->conversion_to_primary;
    }
}
