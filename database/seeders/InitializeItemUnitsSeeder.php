<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Database\Seeder;

class InitializeItemUnitsSeeder extends Seeder
{
    public function run(): void
    {
        // For each existing item, create a primary unit entry
        Item::chunk(100, function ($items) {
            foreach ($items as $item) {
                // Create primary unit (default 1:1 conversion)
                ItemUnit::create([
                    'item_id' => $item->id,
                    'unit_name' => $item->primary_unit ?? $item->unit,
                    'conversion_to_primary' => 1,
                    'is_primary' => true,
                    'context' => 'both',
                ]);
            }
        });

        $this->command->info('Initialized primary units for all existing items.');
    }
}
