<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

class StoredText
{
    /**
     * Pola teks sistem yang disimpan di database dalam format Indonesia.
     * Urutan diperiksa dari pola paling spesifik ke paling umum.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const PATTERNS = [
        ['/^Penyesuaian stok dari (?<from>.+) ke (?<to>.+)$/u', 'Penyesuaian stok dari :from ke :to'],
        ['/^Diterima (?<quantity>\S+) (?<unit>.+?) (?:-|–|—) (?<note>.+)$/u', 'Diterima :quantity :unit - :note'],
        ['/^Diterima (?<quantity>\S+) (?<unit>.+)$/u', 'Diterima :quantity :unit'],
        ['/^Sisa (?<quantity>\S+) (?<unit>.+?) ditutup (?:-|–|—) (?<note>.+)$/u', 'Sisa :quantity :unit ditutup - :note'],
        ['/^Barang: (?<item>.+) (?:-|–|—) (?<rest>.+)$/u', 'Barang: :item - :rest'],
    ];

    /**
     * Terjemahkan teks sistem yang tersimpan di database (reason/note/admin_note)
     * ke locale aktif. Teks yang diketik pengguna tidak ada di daftar pola
     * maupun di file terjemahan sehingga dikembalikan apa adanya.
     */
    public static function translate(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        if (app()->getLocale() !== 'en') {
            return $text;
        }

        if (Lang::has($text)) {
            return __($text);
        }

        foreach (self::PATTERNS as [$pattern, $key]) {
            if (! preg_match($pattern, $text, $matches) || ! Lang::has($key)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return __($key, $params);
        }

        return $text;
    }
}
