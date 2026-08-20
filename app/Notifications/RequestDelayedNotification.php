<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestDelayedNotification extends Notification
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
            'title' => 'Permintaan Ditunda',
            'message' => 'Permintaan ' . $this->stockRequest->quantity . ' ' . $this->stockRequest->unit
                . ' ' . ($this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang')
                . ' ditunda oleh HR (Pending).' . ($note ? ' Catatan: ' . $note : ''),
            'type' => 'delayed',
            'url' => '/gudang/history/' . $this->stockRequest->id,
        ];
    }
}
