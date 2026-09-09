<?php

namespace App\Exports;

class ProcurementNoteExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            ['No', 'Barang', 'Jumlah', 'Diterima', 'Satuan', 'Prioritas', 'Pemohon', 'Status', 'Catatan HR'],
            'procurement-note',
            'Nota Pengadaan'
        );
    }
}
