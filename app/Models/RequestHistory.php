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

    protected $casts = [
        'created_at' => 'datetime',
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
