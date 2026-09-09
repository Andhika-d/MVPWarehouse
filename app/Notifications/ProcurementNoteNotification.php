<?php

namespace App\Notifications;

use App\Models\ProcurementNote;
use Illuminate\Notifications\Notification;

class ProcurementNoteNotification extends Notification
{
    public function __construct(public ProcurementNote $note, public string $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $cancelled = $this->event === 'cancelled';

        return [
            'title' => $cancelled ? 'Nota Pengadaan Dibatalkan' : 'Nota Pengadaan Diterbitkan',
            'message' => $cancelled
                ? $this->note->number.' dibatalkan oleh HR dan tidak perlu diproses.'
                : $this->note->number.' telah diterbitkan dengan '.$this->note->items()->count().' item dan siap diterima.',
            'type' => $cancelled ? 'warning' : 'approved',
            'url' => '/gudang/penerimaan',
        ];
    }
}
