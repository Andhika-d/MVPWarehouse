<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('primary_unit', 50)->default('Pcs')->after('unit');
        });
        
        // Backfill: set primary_unit from existing unit column
        DB::statement('UPDATE items SET primary_unit = unit');
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('primary_unit');
        });
    }
};
