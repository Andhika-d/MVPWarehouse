<?php

namespace App\Models;

use Database\Factories\CoilTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function locationHistories(): HasMany
    {
        return $this->hasMany(CoilTagLocationHistory::class)->latest('id');
    }

    /**
     * The single entry point that changes a coil location.
     *
     * Every change writes an audit row so the movement trail stays consistent,
     * no matter whether it comes from a checker, a manual edit or an import.
     * The value update and the audit row are persisted atomically.
     */
    public function changeLocation(?string $to, string $source, ?int $changedBy = null, ?string $note = null): void
    {
        $to = $to !== null ? CoilLocation::normalize($to) : '';
        $to = $to === '' ? null : $to;

        $from = $this->lokasi_coil === '' ? null : $this->lokasi_coil;

        if ($from === $to) {
            return;
        }

        DB::transaction(function () use ($to, $from, $source, $changedBy, $note): void {
            if ($to !== null) {
                CoilLocation::ensure($to);
            }

            $this->update(['lokasi_coil' => $to]);

            $this->locationHistories()->create([
                'from_location' => $from,
                'to_location' => $to,
                'source' => $source,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);
        });
    }

    public function beratAsScanned(): string
    {
        $fields = str_getcsv((string) $this->raw_kp);

        return isset($fields[6]) && is_numeric($fields[6])
            ? trim($fields[6])
            : (string) $this->berat;
    }
}
