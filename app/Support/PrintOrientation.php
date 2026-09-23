<?php

namespace App\Support;

use Illuminate\Http\Request;

final class PrintOrientation
{
    public static function resolve(Request $request, string $default = 'landscape'): string
    {
        $validated = $request->validate([
            'orientation' => ['nullable', 'in:landscape,portrait'],
        ]);

        return $validated['orientation'] ?? $default;
    }
}