<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add quantity_in_primary to stock_requests
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->decimal('quantity_in_primary', 12, 4)->nullable()->after('quantity');
        });
        
        // Add quantity_in_primary to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity_in_primary', 12, 4)->nullable()->after('quantity');
        });
        
        // Backfill: assume existing data is already in primary unit (1:1 conversion)
        DB::statement('UPDATE stock_requests SET quantity_in_primary = quantity WHERE quantity_in_primary IS NULL');
        DB::statement('UPDATE stock_movements SET quantity_in_primary = quantity WHERE quantity_in_primary IS NULL');
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn('quantity_in_primary');
        });
        
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('quantity_in_primary');
        });
    }
};
