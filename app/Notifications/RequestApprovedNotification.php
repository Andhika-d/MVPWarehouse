<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestApprovedNotification extends Notification
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
            'title_key' => 'Permintaan Disetujui',
            'message_key' => 'Permintaan :quantity :unit :item telah disetujui HR dan siap dibelanjakan.',
            'params' => [
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang'),
            ],
            'type' => 'approved',
            'url' => '/gudang/history/'.$this->stockRequest->id,
        ];
    }
}
