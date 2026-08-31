<?php

namespace App\Exports;

class AuditLogExport extends SimpleXlsx
{
    public function __construct(array $rows)
    {
        parent::__construct(
            $rows,
            ['Waktu', 'User', 'Aksi', 'Target', 'Detail'],
            'audit-export',
            'Audit Log'
        );
    }
}