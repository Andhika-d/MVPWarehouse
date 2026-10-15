<?php

namespace App\Models;

use Database\Factories\CoilTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoilTag extends Model
{
    /** @use HasFactory<CoilTagFactory> */
    use HasFactory;

    protected $fillable = [
        'nomor_coil',
        'grade',
        'tebal',
        'lebar',
        'ukuran',
        'berat',
        'company',
        'field_1',
        'field_6',
        'field_9',
        'tag_taehang',
        'lokasi_coil',
        'user_id',
        'raw_kp',
        'raw_taehang',
        'scanned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tebal' => 'decimal:8',
            'lebar' => 'decimal:8',
            'berat' => 'decimal:8',
            'scanned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beratAsScanned(): string
    {
        $fields = str_getcsv((string) $this->raw_kp);

        return isset($fields[6]) && is_numeric($fields[6])
            ? trim($fields[6])
            : (string) $this->berat;
    }
}
