<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringIssue extends Model
{
    public const SOURCE_DETECTOR = 'detector';

    public const STATUS_OPEN = 'Open';
    public const STATUS_IN_REVIEW = 'Dalam Tinjauan';
    public const STATUS_RESOLVED = 'Selesai';

    public const SEVERITY_NORMAL = 'Normal';
    public const SEVERITY_WARNING = 'Peringatan';
    public const SEVERITY_CRITICAL = 'Kritis';

    public const RULE_REQUEST_AWAITING_REVIEW = 'request_awaiting_review';
    public const RULE_AWAITING_FIRST_RECEIPT = 'awaiting_first_receipt';
    public const RULE_PARTIAL_RECEIPT_STALLED = 'partial_receipt_stalled';
    public const RULE_LOCATION_CHANGE_AWAITING_CONFIRMATION = 'location_change_awaiting_confirmation';

    public const SEVERITIES = [self::SEVERITY_NORMAL, self::SEVERITY_WARNING, self::SEVERITY_CRITICAL];

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_IN_REVIEW, self::STATUS_RESOLVED];

    protected $fillable = [
        'stock_request_id',
        'user_id',
        'source',
        'rule_key',
        'subject_type',
        'subject_id',
        'category',
        'severity',
        'status',
        'description',
        'note',
        'dedupe_key',
        'context',
        'detected_at',
        'last_seen_at',
        'resolved_at',
        'occurrence_count',
    ];

    protected $casts = [
        'context' => 'array',
        'detected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'occurrence_count' => 'integer',
    ];

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_REVIEW], true);
    }
}