<?php

namespace App\Models;

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
        'balance_before' => 'integer',
        'balance_after' => 'integer',
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
