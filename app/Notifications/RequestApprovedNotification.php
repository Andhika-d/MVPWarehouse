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
            'title' => 'Permintaan Disetujui',
            'message' => 'Permintaan ' . $this->stockRequest->quantity . ' ' . $this->stockRequest->unit
                . ' ' . ($this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang')
                . ' telah disetujui HR dan siap dibelanjakan.',
            'type' => 'approved',
            'url' => '/gudang/history/' . $this->stockRequest->id,
        ];
    }
}
