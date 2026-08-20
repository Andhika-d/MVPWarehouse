<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestHistory extends Model
{
    protected $fillable = [
        'stock_request_id',
        'user_id',
        'status',
        'note',
    ];

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
