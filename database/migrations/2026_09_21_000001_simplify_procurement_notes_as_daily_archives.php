<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['procurement_note_id']);
        });

        DB::table('stock_requests')->update(['procurement_note_id' => null]);
        Schema::dropIfExists('procurement_note_items');
        Schema::dropIfExists('procurement_note_sequences');
        Schema::dropIfExists('procurement_notes');

        Schema::create('procurement_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('request_date')->unique();
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->foreign('procurement_note_id')->references('id')->on('procurement_notes')->nullOnDelete();
        });

        $requests = DB::table('stock_requests')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($request) => substr((string) $request->created_at, 0, 10));

        foreach ($requests as $date => $group) {
            $noteId = DB::table('procurement_notes')->insertGetId([
                'number' => 'NOTA-'.str_replace('-', '', $date),
                'request_date' => $date,
                'created_at' => $group->min('created_at'),
                'updated_at' => now(),
            ]);

            DB::table('stock_requests')
                ->whereIn('id', $group->pluck('id'))
                ->update(['procurement_note_id' => $noteId]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['procurement_note_id']);
        });

        DB::table('stock_requests')->update(['procurement_note_id' => null]);
        Schema::dropIfExists('procurement_notes');

        Schema::create('procurement_note_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('procurement_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('status')->default('Draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('driver_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->foreign('procurement_note_id')->references('id')->on('procurement_notes')->nullOnDelete();
        });

        Schema::create('procurement_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->string('unit', 50);
            $table->string('priority', 50)->nullable();
            $table->string('requester_name')->nullable();
            $table->text('review_note')->nullable();
            $table->string('request_status');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['procurement_note_id', 'sort_order']);
            $table->index('stock_request_id');
        });
    }
};
