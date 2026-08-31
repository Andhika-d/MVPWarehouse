<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestClosedNotification extends Notification
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
        $status = $this->stockRequest->status === 'Ditutup Sebagian' ? 'Ditutup Sebagian' : 'Dibatalkan';
        $remaining = $this->stockRequest->remainingQuantity();

        return [
            'title' => $status,
            'message' => 'Sisa ' . $remaining . ' ' . $this->stockRequest->unit
                . ' dari ' . ($this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? 'Barang')
                . ' ditutup. Status:' . $status . '.'
                . ($this->stockRequest->close_note ? ' Alasan: ' . $this->stockRequest->close_note : ''),
            'type' => $this->stockRequest->status === 'Ditutup Sebagian' ? 'closed' : 'cancelled',
            'url' => '/director/requests/' . $this->stockRequest->id,
        ];
    }
}