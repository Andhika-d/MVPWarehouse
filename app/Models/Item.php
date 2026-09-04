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
