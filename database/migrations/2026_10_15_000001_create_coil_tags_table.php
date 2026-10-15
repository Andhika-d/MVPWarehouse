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
        Schema::create('coil_tags', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_coil')->unique();
            $table->text('grade');
            $table->decimal('tebal', 20, 8);
            $table->decimal('lebar', 20, 8);
            $table->string('ukuran');
            $table->decimal('berat', 20, 8);
            $table->string('company')->nullable();
            $table->string('field_1')->nullable();
            $table->string('field_6')->nullable();
            $table->string('field_9')->nullable();
            $table->string('tag_taehang')->unique();
            $table->string('lokasi_coil')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('raw_kp');
            $table->text('raw_taehang');
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('lokasi_coil');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coil_tags');
    }
};
