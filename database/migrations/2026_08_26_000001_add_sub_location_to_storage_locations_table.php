<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->string('sub_location')->nullable()->after('number');
            $table->unique('sub_location');
        });
    }

    public function down(): void
    {
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropUnique(['sub_location']);
            $table->dropColumn('sub_location');
        });
    }
};
