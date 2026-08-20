<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockRequest extends Model
{
    public const ACTIONABLE_STATUSES = ['Menunggu Review', 'Pending'];

    protected $fillable = [
        'user_id',
        'item_id',
        'item_name',
        'quantity',
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

    public function isActionable(): bool
    {
        return in_array($this->status, self::ACTIONABLE_STATUSES, true);
    }

    public function canComplete(): bool
    {
        return $this->status === 'Disetujui' && $this->completed_at === null;
    }
}
