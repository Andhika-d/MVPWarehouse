<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('Umum');
            $table->text('description')->nullable();
            $table->string('audience_role')->default('all');
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['audience_role', 'status']);
        });

        Schema::create('help_guide_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_guide_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('image_alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('help_guide_markers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_guide_image_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->decimal('position_x', 6, 3);
            $table->decimal('position_y', 6, 3);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('help_guide_image_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_guide_markers');
        Schema::dropIfExists('help_guide_images');
        Schema::dropIfExists('help_guides');
    }
};
