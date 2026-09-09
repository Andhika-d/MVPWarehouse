<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ProcurementNote;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProcurementNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_edit_and_issue_a_permanent_note_snapshot(): void
    {
        [$hr, $warehouse, $firstRequest, $secondRequest] = $this->fixtures();

        $this->actingAs($hr)->post(route('hr.procurement-notes.store'), [
            'request_ids' => [$firstRequest->id],
            'driver_name' => 'Budi',
            'notes' => 'Belanja pagi',
        ])->assertRedirect();

        $note = ProcurementNote::with('items')->firstOrFail();
        $this->assertSame('NOTA-'.now()->format('Ymd').'-001', $note->number);
        $this->assertSame(ProcurementNote::STATUS_DRAFT, $note->status);
        $this->assertSame('Mouse Wireless', $note->items->first()->item_name);
        $this->assertSame($note->id, $firstRequest->fresh()->procurement_note_id);

        $this->actingAs($hr)->put(route('hr.procurement-notes.update', $note), [
            'request_ids' => [$firstRequest->id, $secondRequest->id],
            'driver_name' => 'Agus',
        ])->assertRedirect();
        $this->assertCount(2, $note->fresh()->items);

        $this->actingAs($hr)->post(route('hr.procurement-notes.issue', $note))->assertRedirect();
        $note->refresh();
        $this->assertSame(ProcurementNote::STATUS_ISSUED, $note->status);
        $this->assertNotNull($note->issued_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $warehouse->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($hr)->put(route('hr.procurement-notes.update', $note), [
            'request_ids' => [$firstRequest->id],
        ])->assertStatus(422);

        $this->actingAs($hr)->get(route('hr.procurement-notes.print', $note))
            ->assertOk()->assertSee('Mouse Wireless')->assertSee('Agus')->assertSee('Belum Diterima');
        $this->assertNotNull($note->fresh()->last_printed_at);

        $this->actingAs($hr)->get('/hr/nota-pengadaan/'.$note->id.'/pdf')->assertNotFound();
        $this->actingAs($hr)->get('/hr/daftar-belanja/export/preview')->assertNotFound();
        $this->actingAs($hr)->get(route('hr.procurement-notes.show', $note))
            ->assertOk()->assertDontSee('>PDF<', false)->assertDontSee('target="_blank"', false);
    }

    public function test_receipt_updates_note_progress_until_completed(): void
    {
        [$hr, $warehouse, $firstRequest] = $this->fixtures();
        $this->actingAs($hr)->post(route('hr.procurement-notes.store'), ['request_ids' => [$firstRequest->id]]);
        $note = ProcurementNote::firstOrFail();
        $this->actingAs($hr)->post(route('hr.procurement-notes.issue', $note));

        $this->actingAs($warehouse)->post('/gudang/penerimaan', [
            'stock_request_id' => $firstRequest->id,
            'received_quantity' => 1,
        ])->assertRedirect();
        $this->assertSame(ProcurementNote::STATUS_PARTIAL, $note->fresh()->status);
        $this->assertSame(1, $note->items()->first()->received_quantity);
        $this->actingAs($hr)->get(route('hr.procurement-notes.print', $note))
            ->assertOk()->assertSee('Diterima Sebagian (1/2 Pcs)');

        $this->actingAs($warehouse)->post('/gudang/penerimaan', [
            'stock_request_id' => $firstRequest->id,
            'received_quantity' => 1,
        ])->assertRedirect();
        $note->refresh();
        $this->assertSame(ProcurementNote::STATUS_COMPLETED, $note->status);
        $this->assertNotNull($note->completed_at);
        $this->actingAs($hr)->get(route('hr.procurement-notes.print', $note))
            ->assertOk()->assertSee('Diterima Penuh (2/2 Pcs)');
    }

    public function test_cancelling_note_releases_requests_back_to_queue(): void
    {
        [$hr, , $firstRequest] = $this->fixtures();
        $this->actingAs($hr)->post(route('hr.procurement-notes.store'), ['request_ids' => [$firstRequest->id]]);
        $note = ProcurementNote::firstOrFail();

        $this->actingAs($hr)->post(route('hr.procurement-notes.cancel', $note), ['reason' => 'Vendor tidak tersedia'])
            ->assertRedirect();

        $this->assertSame(ProcurementNote::STATUS_CANCELLED, $note->fresh()->status);
        $this->assertNull($firstRequest->fresh()->procurement_note_id);
        $this->assertSame('Dibatalkan', $note->items()->first()->receiptStatusLabel());
        $this->actingAs($hr)->get('/hr/daftar-belanja')->assertOk()->assertSee('Mouse Wireless');
    }

    public function test_exact_date_filter_applies_to_approval_queue_and_note_history(): void
    {
        [$hr, , $firstRequest, $secondRequest] = $this->fixtures();
        $firstRequest->update(['approved_at' => '2026-09-08 09:00:00']);
        $secondRequest->update(['approved_at' => '2026-09-09 09:00:00']);
        ProcurementNote::create([
            'number' => 'NOTA-20260908-099',
            'status' => ProcurementNote::STATUS_ISSUED,
            'created_by' => $hr->id,
            'issued_at' => '2026-09-08 10:00:00',
        ]);
        ProcurementNote::create([
            'number' => 'NOTA-20260909-099',
            'status' => ProcurementNote::STATUS_ISSUED,
            'created_by' => $hr->id,
            'issued_at' => '2026-09-09 10:00:00',
        ]);

        $this->actingAs($hr)->get('/hr/daftar-belanja?date=2026-09-08')
            ->assertOk()
            ->assertDontSee('type="month"', false)
            ->assertSee('Mouse Wireless')
            ->assertDontSee('Keyboard')
            ->assertSee('NOTA-20260908-099')
            ->assertDontSee('NOTA-20260909-099');
    }

    private function fixtures(): array
    {
        $hr = $this->user('HR', 'hr', 'hr-note@example.com');
        $warehouse = $this->user('Gudang', 'gudang', 'warehouse-note@example.com');
        $requester = $this->user('Pemohon', 'karyawan', 'requester-note@example.com');
        $first = $this->approvedRequest($requester, 'Mouse Wireless', 2);
        $second = $this->approvedRequest($requester, 'Keyboard', 3);

        return [$hr, $warehouse, $first, $second];
    }

    private function user(string $name, string $role, string $email): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('password'), 'role' => $role]);
    }

    private function approvedRequest(User $requester, string $name, int $quantity): StockRequest
    {
        $item = Item::create(['name' => $name, 'rack_location' => 'A', 'stock' => 0, 'unit' => 'Pcs']);

        return StockRequest::create([
            'user_id' => $requester->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Operasional',
            'status' => 'Disetujui',
            'approved_at' => now(),
        ]);
    }
}
