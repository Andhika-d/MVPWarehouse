<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_change_requests', function (Blueprint $table) {
            $table->string('from_sub_location')->nullable()->after('from_location_id');
            $table->string('target_sub_location')->nullable()->after('to_location_id');
        });

        Schema::table('storage_locations', function (Blueprint $table) {
            $table->index('sub_location');
        });
    }

    public function down(): void
    {
        Schema::table('location_change_requests', function (Blueprint $table) {
            $table->dropColumn(['from_sub_location', 'target_sub_location']);
        });

        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropIndex(['sub_location']);
        });
    }
};