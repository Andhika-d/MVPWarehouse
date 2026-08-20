<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    public const UNITS = ['Pcs', 'Pck', 'Box', 'Kg', 'Roll', 'Rim'];

    protected $fillable = [
        'name',
        'size',
        'rack_location',
        'stock',
        'unit',
    ];

    public function stockRequests()
    {
        return $this->hasMany(StockRequest::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim($this->name);

        return $this->size ? $name . ' (' . trim($this->size) . ')' : $name;
    }
}
