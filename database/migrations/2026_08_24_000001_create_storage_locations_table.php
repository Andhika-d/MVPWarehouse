<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('rack', 1);
            $table->integer('number');
            $table->string('status')->default('Kosong');
            $table->timestamps();

            $table->index('rack');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
    }
};
