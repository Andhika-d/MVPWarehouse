<?php

namespace App\Exports;

class CoilMovementExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            [
                'No.',
                'Waktu',
                'Jenis',
                'Nomor Coil',
                'Company',
                'Grade',
                'Dari Lokasi',
                'Ke Lokasi',
                'Lokasi Sekarang',
                'Oleh',
                'Sumber',
            ],
            'coil-movement-export',
            'Coil Movement Report'
        );
    }
}
