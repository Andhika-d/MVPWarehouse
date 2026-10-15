<?php

namespace App\Services;

use InvalidArgumentException;

class KpScanParser
{
    /**
     * @return array{
     *     field_1: string,
     *     nomor_coil: string,
     *     grade: string,
     *     tebal: string,
     *     lebar: string,
     *     ukuran: string,
     *     field_6: string,
     *     berat: string,
     *     company: string,
     *     field_9: string,
     *     raw_kp: string
     * }
     */
    public function parse(string $rawKp): array
    {
        $fields = explode(',', $rawKp);

        if (count($fields) === 10 && trim($fields[9]) === '') {
            array_pop($fields);
        }

        if (count($fields) !== 9) {
            throw new InvalidArgumentException('Format KP harus berisi tepat 9 field. Koma di akhir bersifat opsional.');
        }

        $fields = array_map('trim', $fields);

        if ($fields[1] === '') {
            throw new InvalidArgumentException('Nomor coil wajib diisi.');
        }

        if ($fields[2] === '') {
            throw new InvalidArgumentException('Grade wajib diisi.');
        }

        foreach ([3 => 'Tebal', 4 => 'Lebar', 6 => 'Berat'] as $index => $label) {
            if (! is_numeric($fields[$index]) || ! is_finite((float) $fields[$index]) || (float) $fields[$index] <= 0) {
                throw new InvalidArgumentException($label.' harus berupa angka lebih besar dari 0.');
            }
        }

        return [
            'field_1' => $fields[0],
            'nomor_coil' => $fields[1],
            'grade' => $fields[2],
            'tebal' => $fields[3],
            'lebar' => $fields[4],
            'ukuran' => $fields[3].' × '.$fields[4],
            'field_6' => $fields[5],
            'berat' => $fields[6],
            'company' => $fields[7],
            'field_9' => $fields[8],
            'raw_kp' => $rawKp,
        ];
    }
}
