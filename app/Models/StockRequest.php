<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    public const ACTIONABLE_STATUSES = ['Menunggu Review', 'Pending'];
    public const RECEIVABLE_STATUSES = ['Disetujui', 'Sebagian Diterima'];

    protected $fillable = [
        'user_id',
        'item_id',
        'item_name',
        'quantity',
        'received_quantity',
        'unit',
        'priority',
        'reason',
        'attachment_path',
        'status',
        'reviewed_by',
        'review_note',
        'approved_at',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function requestHistories()
    {
        return $this->hasMany(RequestHistory::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isActionable(): bool
    {
        return in_array($this->status, self::ACTIONABLE_STATUSES, true);
    }

    public function canReceive(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true)
            && $this->received_quantity < $this->quantity;
    }

    public function remainingQuantity(): int
    {
        return $this->quantity - $this->received_quantity;
    }
}
