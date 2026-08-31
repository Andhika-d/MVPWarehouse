<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('storage_location_id')->nullable()->after('id')->constrained('storage_locations')->nullOnDelete();
            $table->dropColumn('rack_location');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['storage_location_id']);
            $table->dropColumn('storage_location_id');
            $table->string('rack_location');
        });
    }
};
