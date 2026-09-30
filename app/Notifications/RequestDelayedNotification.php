<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Notifications\Notification;

class RequestDelayedNotification extends Notification
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
            'title_key' => 'Permintaan Ditunda',
            'message_key' => $note
                ? 'Permintaan :quantity :unit :item ditunda oleh HR (Pending). Catatan: :note'
                : 'Permintaan :quantity :unit :item ditunda oleh HR (Pending).',
            'params' => [
                'quantity' => $this->stockRequest->quantity,
                'unit' => $this->stockRequest->unit,
                'item' => $this->stockRequest->item?->name ?? $this->stockRequest->item_name ?? __('Barang'),
                'note' => $note ?? '',
            ],
            'type' => 'delayed',
            'url' => '/gudang/history/'.$this->stockRequest->id,
        ];
    }
}
