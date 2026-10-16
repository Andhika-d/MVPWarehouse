<?php

namespace App\Exports;

class CoilLocationImportTemplate extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            [
                'Nomor Coil',
                'Lokasi',
            ],
            'coil-location-template',
            'Template Import Lokasi'
        );
    }
}
