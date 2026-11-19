<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->string('request_kind')->nullable()->default('master')->after('item_name');
            $table->string('item_size')->nullable()->after('request_kind');
            $table->timestamp('promoted_at')->nullable()->after('procurement_note_id');
            $table->foreignId('promoted_by')->nullable()->after('promoted_at')->constrained('users')->nullOnDelete();
            $table->index('request_kind', 'idx_stock_requests_request_kind');
        });

        // Existing requests all reference a master item.
        DB::table('stock_requests')->whereNull('request_kind')->update(['request_kind' => 'master']);
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropIndex('idx_stock_requests_request_kind');
            $table->dropConstrainedForeignId('promoted_by');
            $table->dropColumn(['promoted_at', 'item_size', 'request_kind']);
        });
    }
};
