<?php

namespace App\Support;

use App\Models\Item;
use Illuminate\Support\Collection;

class ItemLocationSorter
{
    public static function sort(Collection $items): Collection
    {
        return $items->sort(function (Item $a, Item $b): int {
            $locationA = $a->storageLocation;
            $locationB = $b->storageLocation;

            if (! $locationA && ! $locationB) {
                return strnatcasecmp($a->display_name, $b->display_name);
            }

            if (! $locationA) {
                return 1;
            }

            if (! $locationB) {
                return -1;
            }

            $rackComparison = strnatcasecmp((string) $locationA->rack, (string) $locationB->rack);
            if ($rackComparison !== 0) {
                return $rackComparison;
            }

            $subLocationComparison = self::compareLocation((string) ($locationA->sub_location ?? ''), (string) ($locationB->sub_location ?? ''));
            if ($subLocationComparison !== 0) {
                return $subLocationComparison;
            }

            $numberComparison = $locationA->number <=> $locationB->number;
            if ($numberComparison !== 0) {
                return $numberComparison;
            }

            return strnatcasecmp($a->display_name, $b->display_name);
        })->values();
    }

    private static function compareLocation(string $a, string $b): int
    {
        $partsA = array_map('trim', explode('.', $a));
        $partsB = array_map('trim', explode('.', $b));
        $length = max(count($partsA), count($partsB));

        for ($index = 0; $index < $length; $index++) {
            $partA = $partsA[$index] ?? '';
            $partB = $partsB[$index] ?? '';

            if ($partA === $partB) {
                continue;
            }

            if (is_numeric($partA) && is_numeric($partB)) {
                return (float) $partA <=> (float) $partB;
            }

            return strnatcasecmp($partA, $partB);
        }

        return 0;
    }
}
