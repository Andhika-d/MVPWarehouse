<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coil_locations', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaults = ['AL', 'AR', 'BL', 'BR', 'CL', 'CR'];

        DB::table('coil_locations')->insert(array_map(fn (string $kode, int $index): array => [
            'kode' => $kode,
            'nama' => null,
            'urutan' => $index + 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $defaults, array_keys($defaults)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coil_locations');
    }
};
