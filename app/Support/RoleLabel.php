<?php

namespace App\Support;

final class RoleLabel
{
    public static function of(?string $role): string
    {
        return match ($role) {
            'admin' => __('ADMIN'),
            'hr' => __('HR'),
            'director' => __('DIREKTUR'),
            'gudang' => __('GUDANG'),
            'checker' => __('CHECKER'),
            'admin_coil' => __('ADMIN COIL'),
            default => strtoupper((string) $role),
        };
    }
}
