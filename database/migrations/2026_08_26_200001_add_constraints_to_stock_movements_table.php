<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_stock_movements_item_type ON stock_movements (item_id, type)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_stock_movements_occurred_at ON stock_movements (occurred_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_stock_movements_user ON stock_movements (user_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_stock_movements_item_type');
        DB::statement('DROP INDEX IF EXISTS idx_stock_movements_occurred_at');
        DB::statement('DROP INDEX IF EXISTS idx_stock_movements_user');
    }
};
