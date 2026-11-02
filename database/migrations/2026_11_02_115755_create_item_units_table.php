<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('unit_name', 50);
            $table->decimal('conversion_to_primary', 10, 4)->default(1);
            $table->boolean('is_primary')->default(false);
            $table->enum('context', ['purchase', 'usage', 'both'])->default('both');
            $table->timestamps();
            
            $table->index(['item_id', 'unit_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
