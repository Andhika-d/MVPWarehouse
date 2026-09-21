<?php

namespace App\Exports;

class ProcurementNoteExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            ['Nota', 'ID Request', 'Waktu Request', 'Barang', 'Pemohon', 'Diminta', 'Satuan', 'Prioritas', 'Status', 'Tanggal Keputusan', 'Diterima', 'Ditutup', 'Sisa Aktif', 'Selesai/Ditutup', 'Catatan HR', 'Alasan Penutupan', 'Ditutup Oleh'],
            'procurement-note',
            'Riwayat Pengadaan'
        );
    }
}
