<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('setting.'.$key, 3600, function () use ($key, $default) {
            $row = static::where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('setting.'.$key);
    }

    public static function enabled(string $key): bool
    {
        return (bool) static::get($key, false);
    }
}
