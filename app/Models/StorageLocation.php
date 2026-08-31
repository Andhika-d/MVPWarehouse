<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageLocation extends Model
{
    public const STATUS_EMPTY = 'Kosong';
    public const STATUS_OCCUPIED = 'Terisi';

    protected $fillable = [
        'code',
        'rack',
        'number',
        'sub_location',
        'status',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'storage_location_id');
    }

    public function item()
    {
        return $this->hasOne(Item::class, 'storage_location_id');
    }

    public function locationChangeRequests()
    {
        return $this->hasMany(LocationChangeRequest::class, 'from_location_id');
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isEmpty(): bool
    {
        return $this->status === self::STATUS_EMPTY;
    }

    public function syncStatus(): void
    {
        $hasItem = $this->items()->exists();
        $shouldBe = $hasItem ? self::STATUS_OCCUPIED : self::STATUS_EMPTY;

        if ($this->status !== $shouldBe) {
            $this->update(['status' => $shouldBe]);
        }
    }

    public static function getNextCodeForRack(string $rack): string
    {
        $lastNumber = static::where('rack', $rack)->max('number') ?? 0;

        return $rack . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    public static function findNextEmptyForRack(string $rack): ?self
    {
        return static::where('rack', $rack)
            ->where('status', self::STATUS_EMPTY)
            ->orderBy('number')
            ->first();
    }

    public static function getPrefixForRack(string $rack): string
    {
        return match ($rack) {
            'A' => 'RA',
            'B' => 'RB',
            'C' => 'RC',
            'D' => 'RD',
            'E' => 'RE',
            default => 'R' . $rack,
        };
    }

    public function getShortCodeAttribute(): string
    {
        return self::getPrefixForRack($this->rack) . '-' . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    }

    public function getSubLocationDisplayAttribute(): string
    {
        return $this->sub_location ?? '—';
    }
}
