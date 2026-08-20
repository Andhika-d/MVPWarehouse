<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestRejectedNotification extends Notification
{
    public function __construct(public StockRequest $stockRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $note = $this->stockRequest->review_note;

        return [
            'title' => 'Permintaan Ditolak',
            'message' => 'Permintaan ' . $this->stockRequest->quantity . ' ' . $this->stockRequest->unit
                . ' ' . ($this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang')
                . ' ditolak HR.' . ($note ? ' Alasan: ' . $note : ''),
            'type' => 'rejected',
            'url' => '/gudang/history/' . $this->stockRequest->id,
        ];
    }
}
