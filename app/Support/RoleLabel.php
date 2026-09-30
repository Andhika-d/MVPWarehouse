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
            default => strtoupper((string) $role),
        };
    }
}
