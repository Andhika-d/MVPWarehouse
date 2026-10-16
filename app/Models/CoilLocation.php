<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CoilLocation extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'urutan',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function normalize(string $kode): string
    {
        return mb_strtoupper(trim($kode));
    }

    /**
     * Normalize the code and make sure it exists in the master list.
     */
    public static function ensure(string $kode): self
    {
        $kode = self::normalize($kode);

        if ($kode === '') {
            throw new \InvalidArgumentException('Kode lokasi wajib diisi.');
        }

        return self::firstOrCreate(
            ['kode' => $kode],
            ['urutan' => (int) self::max('urutan') + 1],
        );
    }
}
