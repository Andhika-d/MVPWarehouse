<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropUnique('storage_locations_sub_location_unique');
        });
    }

    public function down(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->unique('sub_location');
        });
    }
};
