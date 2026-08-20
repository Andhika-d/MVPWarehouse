<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
