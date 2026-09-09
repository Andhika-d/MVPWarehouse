<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementNoteItem extends Model
{
    protected $fillable = [
        'procurement_note_id',
        'stock_request_id',
        'item_id',
        'item_name',
        'quantity',
        'received_quantity',
        'unit',
        'priority',
        'requester_name',
        'review_note',
        'request_status',
        'sort_order',
    ];

    public function note()
    {
        return $this->belongsTo(ProcurementNote::class, 'procurement_note_id');
    }

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function receiptStatusLabel(): string
    {
        $progress = $this->received_quantity.'/'.$this->quantity.' '.$this->unit;

        if ($this->request_status === 'Dibatalkan') {
            return 'Dibatalkan';
        }

        if ($this->request_status === 'Ditutup Sebagian') {
            return 'Ditutup Sebagian ('.$progress.')';
        }

        if ($this->received_quantity <= 0) {
            return 'Belum Diterima';
        }

        if ($this->received_quantity >= $this->quantity || $this->request_status === 'Diterima Penuh') {
            return 'Diterima Penuh ('.$progress.')';
        }

        return 'Diterima Sebagian ('.$progress.')';
    }
}
