<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['item_id', 'type'], 'idx_stock_movements_item_type');
            $table->index('occurred_at', 'idx_stock_movements_occurred_at');
            $table->index('user_id', 'idx_stock_movements_user');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_movements_item_type');
            $table->dropIndex('idx_stock_movements_occurred_at');
            $table->dropIndex('idx_stock_movements_user');
        });
    }
};
