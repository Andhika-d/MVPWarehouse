<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_location_id')->constrained('storage_locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Menunggu Konfirmasi');
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_change_requests');
    }
};
