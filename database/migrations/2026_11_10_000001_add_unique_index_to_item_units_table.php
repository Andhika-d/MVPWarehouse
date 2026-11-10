<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'unit_name']);
            $table->unique(['item_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        Schema::table('item_units', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'unit_name']);
            $table->index(['item_id', 'unit_name']);
        });
    }
};
