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
        $item = $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang');

        return [
            'title_key' => 'Permintaan Baru Masuk',
            'message_key' => ':user mengajukan :quantity :unit :item (:priority).',
            'params' => [
                'user' => $this->stockRequest->user?->name ?? __('Gudang'),
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $item,
                'priority' => $this->stockRequest->priority,
            ],
            'type' => 'new_request',
            'priority' => $this->stockRequest->priority,
            'url' => '/hr/approval',
        ];
    }
}
