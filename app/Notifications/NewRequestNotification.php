<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification
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
        $item = $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang';

        return [
            'title' => 'Permintaan Baru Masuk',
            'message' => ($this->stockRequest->user?->name ?? 'Gudang')
                . ' mengajukan ' . $this->stockRequest->quantity . ' ' . $this->stockRequest->unit
                . ' ' . $item . ' (' . $this->stockRequest->priority . ').',
            'type' => 'new_request',
            'priority' => $this->stockRequest->priority,
            'url' => '/hr/approval',
        ];
    }
}
