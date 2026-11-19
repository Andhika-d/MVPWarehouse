<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL needs the old index replaced in one ALTER statement because it
        // is currently used by a foreign key constraint.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `item_units` DROP INDEX `item_units_item_id_unit_name_index`, ADD UNIQUE INDEX `item_units_item_id_unit_name_unique` (`item_id`, `unit_name`)');

            return;
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'unit_name']);
            $table->unique(['item_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `item_units` DROP INDEX `item_units_item_id_unit_name_unique`, ADD INDEX `item_units_item_id_unit_name_index` (`item_id`, `unit_name`)');

            return;
        }

        Schema::table('item_units', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'unit_name']);
            $table->index(['item_id', 'unit_name']);
        });
    }
};
