<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CoilLocation extends Model
{
    /**
     * Sentinel used by list filters to select coils that have no location yet.
     */
    public const FILTER_NONE = '__none__';

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
     * Every value accepted by a location filter: the whole master list, any value
     * still stored on coils (so old rows never become unfilterable) and the
     * "no location" sentinel.
     *
     * @return array<int, string>
     */
    public static function filterOptions(): array
    {
        $codes = self::query()
            ->orderBy('urutan')
            ->orderBy('kode')
            ->pluck('kode');

        $unknown = CoilTag::query()
            ->whereNotNull('lokasi_coil')
            ->distinct()
            ->pluck('lokasi_coil')
            ->reject(fn (string $kode) => $codes->contains($kode))
            ->values();

        return $codes
            ->concat($unknown)
            ->push(self::FILTER_NONE)
            ->all();
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
