<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->foreignId('procurement_note_id')->nullable()->constrained('procurement_notes')->nullOnDelete();
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

        $eligibleStatuses = ['Disetujui', 'Sebagian Diterima', 'Diterima Penuh', 'Ditutup Sebagian', 'Dibatalkan'];
        $requests = DB::table('stock_requests')
            ->leftJoin('items', 'items.id', '=', 'stock_requests.item_id')
            ->leftJoin('users', 'users.id', '=', 'stock_requests.user_id')
            ->whereIn('stock_requests.status', $eligibleStatuses)
            ->orderBy('stock_requests.id')
            ->select([
                'stock_requests.*',
                'items.name as current_item_name',
                'users.name as requester_name',
            ])->get()->groupBy(function ($request) {
                return substr((string) ($request->approved_at ?: $request->created_at), 0, 10);
            });

        foreach ($requests as $date => $group) {
            $issuedAt = $group->min(fn ($request) => $request->approved_at ?: $request->created_at);
            $now = now();
            $noteId = DB::table('procurement_notes')->insertGetId([
                'number' => null,
                'status' => $this->backfillStatus($group),
                'created_by' => $group->first()->reviewed_by,
                'issued_at' => $issuedAt,
                'completed_at' => $group->every(fn ($request) => in_array($request->status, ['Diterima Penuh', 'Ditutup Sebagian', 'Dibatalkan'], true)) ? $now : null,
                'created_at' => $issuedAt,
                'updated_at' => $now,
            ]);
            DB::table('procurement_notes')->where('id', $noteId)->update([
                'number' => 'NOTA-'.str_replace('-', '', $date).'-001',
            ]);
            DB::table('procurement_note_sequences')->insert(['date' => $date, 'last_number' => 1]);

            foreach ($group->values() as $index => $request) {
                DB::table('procurement_note_items')->insert([
                    'procurement_note_id' => $noteId,
                    'stock_request_id' => $request->id,
                    'item_id' => $request->item_id,
                    'item_name' => $request->current_item_name ?: $request->item_name ?: 'Barang',
                    'quantity' => $request->quantity,
                    'received_quantity' => $request->received_quantity,
                    'unit' => $request->unit,
                    'priority' => $request->priority,
                    'requester_name' => $request->requester_name,
                    'review_note' => $request->review_note,
                    'request_status' => $request->status,
                    'sort_order' => $index,
                    'created_at' => $issuedAt,
                    'updated_at' => $now,
                ]);
                DB::table('stock_requests')->where('id', $request->id)->update(['procurement_note_id' => $noteId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_note_items');
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procurement_note_id');
        });
        Schema::dropIfExists('procurement_notes');
        Schema::dropIfExists('procurement_note_sequences');
    }

    private function backfillStatus($requests): string
    {
        if ($requests->every(fn ($request) => in_array($request->status, ['Diterima Penuh', 'Ditutup Sebagian', 'Dibatalkan'], true))) {
            return 'Selesai';
        }

        if ($requests->contains(fn ($request) => $request->received_quantity > 0 || $request->status === 'Sebagian Diterima')) {
            return 'Sebagian Diterima';
        }

        return 'Diterbitkan';
    }
};
