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
        Schema::create('coil_tag_location_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coil_tag_id')->constrained('coil_tags')->cascadeOnDelete();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('source');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['coil_tag_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coil_tag_location_histories');
    }
};
