<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_requests', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('stock_requests', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete()->after('closed_at');
            }
            if (! Schema::hasColumn('stock_requests', 'close_note')) {
                $table->string('close_note')->nullable()->after('closed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('storage_locations', 'closed_by')) {
                $table->dropForeign(['closed_by']);
            }
            foreach (['close_note', 'closed_by', 'closed_at'] as $column) {
                if (Schema::hasColumn('stock_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};