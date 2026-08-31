<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_change_requests', function (Blueprint $table) {
            $table->string('resolution_action')->nullable()->after('target_sub_location');
            $table->foreignId('swap_item_id')->nullable()->after('resolution_action')->constrained('items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('location_change_requests', function (Blueprint $table) {
            $table->dropForeign(['swap_item_id']);
            $table->dropColumn(['resolution_action', 'swap_item_id']);
        });
    }
};