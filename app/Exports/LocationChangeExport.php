<?php

namespace App\Exports;

class LocationChangeExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            [
                'ID',
                'Barang',
                'Kode Asal',
                'Sub Asal',
                'Kode Tujuan',
                'Sub Tujuan',
                'Pemohon',
                'Status',
                'Aksi',
                'Diajukan',
                'Diputuskan',
                'Alasan',
                'Catatan',
            ],
            'loc-export',
            'Riwayat Pengajuan Lokasi'
        );
    }
}