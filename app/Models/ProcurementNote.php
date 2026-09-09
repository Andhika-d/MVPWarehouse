<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementNote extends Model
{
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_ISSUED = 'Diterbitkan';
    public const STATUS_PARTIAL = 'Sebagian Diterima';
    public const STATUS_COMPLETED = 'Selesai';
    public const STATUS_CANCELLED = 'Dibatalkan';

    protected $fillable = [
        'number',
        'status',
        'created_by',
        'driver_name',
        'notes',
        'issued_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'last_printed_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_printed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ProcurementNoteItem::class)->orderBy('sort_order');
    }

    public function requests()
    {
        return $this->hasMany(StockRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function recalculateStatus(): void
    {
        if ($this->isDraft() || $this->status === self::STATUS_CANCELLED) {
            return;
        }

        $items = $this->items()->get();
        if ($items->isEmpty()) {
            return;
        }

        $terminal = ['Diterima Penuh', 'Ditutup Sebagian', 'Dibatalkan'];
        if ($items->every(fn ($item) => in_array($item->request_status, $terminal, true))) {
            $this->update(['status' => self::STATUS_COMPLETED, 'completed_at' => $this->completed_at ?: now()]);
        } elseif ($items->contains(fn ($item) => $item->received_quantity > 0 || $item->request_status === 'Sebagian Diterima')) {
            $this->update(['status' => self::STATUS_PARTIAL, 'completed_at' => null]);
        } else {
            $this->update(['status' => self::STATUS_ISSUED, 'completed_at' => null]);
        }
    }

    public static function syncFromRequest(StockRequest $request): void
    {
        if (! $request->procurement_note_id) {
            return;
        }

        ProcurementNoteItem::where('procurement_note_id', $request->procurement_note_id)
            ->where('stock_request_id', $request->id)
            ->update([
                'received_quantity' => $request->received_quantity,
                'request_status' => $request->status,
                'updated_at' => now(),
            ]);

        self::find($request->procurement_note_id)?->recalculateStatus();
    }
}
