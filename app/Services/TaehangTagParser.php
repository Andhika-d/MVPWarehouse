<?php

namespace App\Services;

use InvalidArgumentException;

class TaehangTagParser
{
    /**
     * @return array{tag_taehang: string, raw_taehang: string}
     */
    public function parse(string $rawTaehang): array
    {
        $tag = trim($rawTaehang);

        if (preg_match('/^\d{2}-\d{3}$/', $tag) !== 1) {
            throw new InvalidArgumentException('Format QR Taehang harus 2 digit, tanda hubung, lalu 3 digit (contoh: 09-020).');
        }

        return [
            'tag_taehang' => $tag,
            'raw_taehang' => $rawTaehang,
        ];
    }
}
