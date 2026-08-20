<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_requests', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('stock_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('review_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stock_requests', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
            if (Schema::hasColumn('stock_requests', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
