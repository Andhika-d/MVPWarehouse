<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_requests', 'received_quantity')) {
                $table->integer('received_quantity')->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('stock_requests', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stock_requests', 'received_quantity')) {
                $table->dropColumn('received_quantity');
            }
            if (Schema::hasColumn('stock_requests', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
