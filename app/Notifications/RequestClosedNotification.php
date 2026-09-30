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
        $partial = $this->stockRequest->status === 'Ditutup Sebagian';
        $status = $partial ? 'Ditutup Sebagian' : 'Dibatalkan';
        $note = $this->stockRequest->close_note;

        return [
            'title_key' => $status,
            'message_key' => $note
                ? 'Sisa :remaining :unit dari :item ditutup. Status: :status. Alasan: :note'
                : 'Sisa :remaining :unit dari :item ditutup. Status: :status.',
            'params' => [
                'remaining' => $this->stockRequest->remainingQuantity(),
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang'),
                'status' => $status,
                'note' => $note ?? '',
            ],
            'type' => $partial ? 'closed' : 'cancelled',
            'url' => '/director/requests/'.$this->stockRequest->id,
        ];
    }
}
