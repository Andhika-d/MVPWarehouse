<?php

namespace App\Models;

use App\Casts\NumericValue;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    protected $fillable = [
        'item_id',
        'type',
        'quantity',
        'quantity_in_primary',
        'unit',
        'reason',
        'stock_request_id',
        'user_id',
        'balance_before',
        'balance_after',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'quantity' => NumericValue::class,
        'quantity_in_primary' => NumericValue::class,
        'balance_before' => NumericValue::class,
        'balance_after' => NumericValue::class,
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
