<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoilTagLocationHistory extends Model
{
    public const SOURCE_CHECKER = 'checker';

    public const SOURCE_ADMIN_MANUAL = 'admin_manual';

    public const SOURCE_ADMIN_IMPORT = 'admin_import';

    protected $fillable = [
        'coil_tag_id',
        'from_location',
        'to_location',
        'source',
        'changed_by',
        'note',
    ];

    public function coilTag(): BelongsTo
    {
        return $this->belongsTo(CoilTag::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_CHECKER => __('Checker'),
            self::SOURCE_ADMIN_MANUAL => __('Admin Coil'),
            self::SOURCE_ADMIN_IMPORT => __('Import'),
            default => $this->source,
        };
    }
}
