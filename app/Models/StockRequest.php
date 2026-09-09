<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockRequest extends Model
{
    public const ACTIONABLE_STATUSES = ['Menunggu Review', 'Pending'];
    public const RECEIVABLE_STATUSES = ['Disetujui', 'Sebagian Diterima'];
    public const CLOSED_STATUSES = ['Ditutup Sebagian', 'Dibatalkan'];

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
        'closed_at',
        'closed_by',
        'close_note',
        'procurement_note_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
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

    public function procurementNote()
    {
        return $this->belongsTo(ProcurementNote::class);
    }

    public function isActionable(): bool
    {
        return in_array($this->status, self::ACTIONABLE_STATUSES, true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function canReceive(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true)
            && $this->received_quantity < $this->quantity;
    }

    public function canClose(): bool
    {
        return in_array($this->status, self::RECEIVABLE_STATUSES, true)
            && $this->received_quantity < $this->quantity;
    }

    public function remainingQuantity(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Tutup sisa request yang tidak akan dipenuhi lagi.
     * Aman dipanggil dari HR maupun Gudang — yang pertama menang,
     * pemanggil berikutnya mendapatkan false.
     *
     * @return array{success: bool, message: string, status: ?string}
     */
    public function closeRemaining(int $closingUserId, string $note): array
    {
        return DB::transaction(function () use ($closingUserId, $note) {
            $locked = self::lockForUpdate()->find($this->id);

            if (! $locked) {
                return [
                    'success' => false,
                    'message' => 'Request tidak ditemukan.',
                    'status' => null,
                ];
            }

            if ($locked->isClosed()) {
                $closerName = $locked->closedBy?->name ?? '—';
                $closerTime = $locked->closed_at?->translatedFormat('d M Y, H:i') ?? '—';

                return [
                    'success' => false,
                    'message' => "Request ini sudah ditutup oleh {$closerName} pada {$closerTime}.",
                    'status' => null,
                ];
            }

            if (! $locked->canClose()) {
                return [
                    'success' => false,
                    'message' => 'Request ini sudah tidak bisa ditutup atau sudah diproses.',
                    'status' => null,
                ];
            }

            if (trim($note) === '') {
                return [
                    'success' => false,
                    'message' => 'Alasan penutupan wajib diisi.',
                    'status' => null,
                ];
            }

            $newStatus = $locked->received_quantity > 0 ? 'Ditutup Sebagian' : 'Dibatalkan';
            $remaining = $locked->remainingQuantity();
            $itemName = $locked->item?->name ?? $locked->item_name ?? 'Barang';

            $locked->update([
                'status' => $newStatus,
                'closed_at' => now(),
                'closed_by' => $closingUserId,
                'close_note' => $note,
            ]);

            ProcurementNote::syncFromRequest($locked->fresh());

            $locked->requestHistories()->create([
                'user_id' => $closingUserId,
                'status' => $newStatus,
                'note' => 'Sisa ' . $remaining . ' ' . $locked->unit . ' ditutup — ' . $note,
            ]);

            AuditLog::create([
                'user_id' => $closingUserId,
                'action' => 'closed_request',
                'target_type' => StockRequest::class,
                'target_id' => $locked->id,
                'details' => 'Menutup sisa ' . $remaining . ' ' . $locked->unit . ' ' . $itemName . ' (' . $newStatus . '): ' . $note,
            ]);

            $locked->refresh();

            // Notify pihak terkait (pemohon jika HR yang menutup, atau semua HR aktif jika gudang yang menutup requestnya sendiri)
            $notifiable = $closingUserId === $locked->user_id
                ? User::where('role', 'hr')->where('is_active', true)->get()
                : $locked->user;

            if ($notifiable) {
                \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\RequestClosedNotification($locked));
            }

            return [
                'success' => true,
                'message' => 'Sisa ' . $remaining . ' ' . $locked->unit . ' ditutup. Status: ' . $newStatus,
                'status' => $newStatus,
            ];
        });
    }
}
