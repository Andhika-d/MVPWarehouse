<?php

namespace App\Exports;

class CoilTagExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            [
                'No.',
                'Company',
                'Tag Taehang',
                'Nomor Coil',
                'Spec',
                'Tebal',
                'Lebar',
                'Net WT/Berat Bersih',
                'Qty.',
                'Lokasi',
                'Scanned At',
            ],
            'coil-export',
            'Coil Tag Report'
        );
    }
}
