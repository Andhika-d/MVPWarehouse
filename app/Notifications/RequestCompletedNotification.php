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
            'title' => 'Belanja Selesai',
            'message' => 'Barang ' . $this->stockRequest->quantity . ' ' . $this->stockRequest->unit
                . ' ' . ($this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang')
                . ' sudah dibelanjakan Driver dan diterima Gudang.',
            'type' => 'completed',
            'url' => '/gudang/history/' . $this->stockRequest->id,
        ];
    }
}
