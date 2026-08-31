<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationChangeRequest extends Model
{
    public const STATUS_PENDING = 'Menunggu Konfirmasi';
    public const STATUS_APPROVED = 'Disetujui';
    public const STATUS_REJECTED = 'Ditolak';

    protected $fillable = [
        'item_id',
        'from_location_id',
        'to_location_id',
        'from_sub_location',
        'target_sub_location',
        'resolution_action',
        'swap_item_id',
        'requested_by',
        'approved_by',
        'status',
        'reason',
        'admin_note',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'swap_item_id' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'to_location_id');
    }

    public function swapItem()
    {
        return $this->belongsTo(Item::class, 'swap_item_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSubLocationChange(): bool
    {
        return $this->target_sub_location !== null;
    }

    public function approve(int $adminId, ?string $note = null): bool
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $adminId,
            'admin_note' => $note,
            'decided_at' => now(),
        ]);

        return true;
    }

    public function reject(int $adminId, ?string $note = null): bool
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $adminId,
            'admin_note' => $note,
            'decided_at' => now(),
        ]);

        return true;
    }
}
