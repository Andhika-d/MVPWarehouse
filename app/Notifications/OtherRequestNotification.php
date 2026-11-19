<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class OtherRequestNotification extends Notification
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
        return [
            'title_key' => 'Permintaan Lainnya Masuk',
            'message_key' => ':user mengajukan :quantity :unit :item di luar rak.',
            'params' => [
                'user' => $this->stockRequest->user?->name ?? __('Gudang'),
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->displayName(),
            ],
            'type' => 'other_request',
            'priority' => $this->stockRequest->priority,
            'url' => '/admin/other-requests',
        ];
    }
}
