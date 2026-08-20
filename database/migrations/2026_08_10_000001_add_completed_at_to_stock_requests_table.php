<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stock_requests', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });
    }
};
