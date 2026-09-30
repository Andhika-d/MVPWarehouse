<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestRejectedNotification extends Notification
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
            'title_key' => 'Permintaan Ditolak',
            'message_key' => $note
                ? 'Permintaan :quantity :unit :item ditolak HR. Alasan: :note'
                : 'Permintaan :quantity :unit :item ditolak HR.',
            'params' => [
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang'),
                'note' => $note ?? '',
            ],
            'type' => 'rejected',
            'url' => '/gudang/history/'.$this->stockRequest->id,
        ];
    }
}
