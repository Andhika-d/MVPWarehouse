<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringIssue extends Model
{
    protected $fillable = [
        'stock_request_id',
        'user_id',
        'category',
        'severity',
        'status',
        'description',
        'note',
    ];

    public const SEVERITIES = ['Normal', 'Peringatan', 'Kritis'];

    public const STATUSES = ['Open', 'Dalam Tinjauan', 'Selesai'];

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
