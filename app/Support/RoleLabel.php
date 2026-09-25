<?php

namespace App\Support;

final class RoleLabel
{
    public static function of(?string $role): string
    {
        return match ($role) {
            'admin' => 'ADMIN',
            'hr' => 'HR',
            'director' => 'DIREKTUR',
            'gudang' => 'GUDANG',
            default => strtoupper((string) $role),
        };
    }
}
