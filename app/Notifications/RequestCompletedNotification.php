<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestCompletedNotification extends Notification
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
            'title_key' => 'Belanja Selesai',
            'message_key' => 'Barang :quantity :unit :item telah diterima Gudang.',
            'params' => [
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang'),
            ],
            'type' => 'completed',
            'url' => '/gudang/history/'.$this->stockRequest->id,
        ];
    }
}
