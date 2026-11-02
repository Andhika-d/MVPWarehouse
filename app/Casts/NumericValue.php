<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast numeric database values to int when they are whole numbers,
 * and to float when they contain decimals.
 *
 * This keeps quantity/stock columns usable as decimals (e.g. 1.5 Kg)
 * without turning whole values such as 3 into 3.0 in comparisons
 * and displays.
 */
class NumericValue implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): int|float|null
    {
        if ($value === null) {
            return null;
        }

        $number = (float) $value;

        if (floor($number) === $number && abs($number) <= PHP_INT_MAX) {
            return (int) $number;
        }

        return $number;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int|float|null
    {
        return $value;
    }
}
