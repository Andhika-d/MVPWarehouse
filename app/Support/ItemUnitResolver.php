<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Support\Facades\Auth;

class ItemUnitResolver
{
    /**
     * Pastikan satuan yang dipilih valid untuk item.
     *
     * Satuan yang sudah terdaftar selalu diterima tanpa mengubah konversinya.
     * Satuan standar (Item::UNITS) yang belum terdaftar boleh ditambahkan
     * langsung dari form transaksi dengan konteks "both".
     *
     * @return array{ok: bool, message: ?string, created: bool}
     */
    public static function ensure(Item $item, ?string $unit, ?float $conversionToPrimary = null, string $source = 'transaksi'): array
    {
        $unit = trim((string) $unit);
        $primaryUnit = $item->primary_unit ?? $item->unit;

        if ($unit === '') {
            return ['ok' => false, 'message' => __('Satuan wajib diisi.'), 'created' => false];
        }

        if ($unit === $primaryUnit) {
            return ['ok' => true, 'message' => null, 'created' => false];
        }

        $existing = $item->itemUnits->firstWhere('unit_name', $unit);

        if ($existing) {
            return ['ok' => true, 'message' => null, 'created' => false];
        }

        if (! in_array($unit, Item::UNITS, true)) {
            return ['ok' => false, 'message' => __('Satuan tidak valid untuk barang ini.'), 'created' => false];
        }

        if ($conversionToPrimary === null || $conversionToPrimary <= 0) {
            return [
                'ok' => false,
                'message' => __('Konversi untuk satuan :unit wajib diisi.', ['unit' => $unit]),
                'created' => false,
            ];
        }

        $itemUnit = $item->itemUnits()->firstOrCreate(
            ['unit_name' => $unit],
            [
                'conversion_to_primary' => $conversionToPrimary,
                'is_primary' => false,
                'context' => 'both',
            ]
        );

        $created = $itemUnit->wasRecentlyCreated;

        if ($created) {
            $item->load('itemUnits');

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created_item_unit',
                'target_type' => ItemUnit::class,
                'target_id' => $itemUnit->id,
                'details' => 'Menambahkan satuan '.$unit.' (1 '.$unit.' = '.$conversionToPrimary.' '.$primaryUnit.') untuk '.$item->name.' dari '.$source,
            ]);
        }

        return ['ok' => true, 'message' => null, 'created' => $created];
    }

    /**
     * Nama satuan standar yang belum dimiliki item.
     *
     * @return array<int, string>
     */
    public static function missingStandardUnits(Item $item): array
    {
        $primaryUnit = $item->primary_unit ?? $item->unit;
        $known = $item->itemUnits
            ->pluck('unit_name')
            ->push($primaryUnit)
            ->filter()
            ->unique();

        return collect(Item::UNITS)
            ->reject(fn (string $unit) => $known->contains($unit))
            ->values()
            ->all();
    }
}
