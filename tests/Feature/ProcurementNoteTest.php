<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ProcurementNote;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_created_on_the_same_day_share_an_automatic_note(): void
    {
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $firstItem = $this->item('Mouse Wireless');
        $secondItem = $this->item('Keyboard');

        $this->travelTo(now()->setDate(2026, 9, 21)->startOfDay()->addHours(9));
        $this->submitRequest($warehouse, $firstItem, 2);
        $this->submitRequest($warehouse, $secondItem, 3);

        $note = ProcurementNote::with('requests')->sole();

        $this->assertSame('NOTA-20260921', $note->number);
        $this->assertSame('2026-09-21', $note->request_date->toDateString());
        $this->assertCount(2, $note->requests);
        $this->assertSame(ProcurementNote::STATUS_ACTIVE, $note->statusLabel());
    }

    public function test_note_status_follows_request_receipt_progress(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Kertas A4'), 2);

        $request = StockRequest::firstOrFail();
        $note = $request->procurementNote;

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve')->assertRedirect('/hr/approval');
        $this->assertSame(ProcurementNote::STATUS_ACTIVE, $note->fresh()->statusLabel());

        $this->actingAs($warehouse)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 1,
        ])->assertRedirect();
        $this->assertSame('Sebagian Diterima', $request->fresh()->status);
        $this->assertSame(ProcurementNote::STATUS_ACTIVE, $note->fresh()->statusLabel());

        $this->actingAs($warehouse)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 1,
        ])->assertRedirect();
        $this->assertSame('Diterima Penuh', $request->fresh()->status);
        $this->assertSame(ProcurementNote::STATUS_COMPLETED, $note->fresh()->statusLabel());
    }

    public function test_hr_bulk_actions_only_update_submitted_request_ids(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Mouse'), 1);
        $this->submitRequest($warehouse, $this->item('Keyboard'), 1);
        [$first, $second] = StockRequest::orderBy('id')->get();

        $this->actingAs($hr)->post(route('hr.requests.bulk-approve'), [
            'request_ids' => [$first->id],
        ])->assertRedirect('/hr/approval');

        $this->assertSame('Disetujui', $first->fresh()->status);
        $this->assertSame('Menunggu Review', $second->fresh()->status);
        $this->actingAs($hr)->post(route('hr.requests.bulk-approve'), [])
            ->assertSessionHasErrors('request_ids');
    }

    public function test_hr_can_cancel_an_unreceived_request_from_note_detail(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Toner'), 1);
        $request = StockRequest::firstOrFail();

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve');
        $this->actingAs($hr)->get(route('procurement-notes.show', $request->procurementNote))
            ->assertOk()
            ->assertSee('Batalkan Request');

        $this->actingAs($hr)->post(route('hr.requests.close', $request), [
            'note' => 'Kebutuhan dibatalkan',
        ])->assertSessionHas('success');

        $this->assertSame('Dibatalkan', $request->fresh()->status);
        $this->assertSame(ProcurementNote::STATUS_COMPLETED, $request->procurementNote->statusLabel());
    }

    public function test_procurement_history_is_available_to_operational_roles_only(): void
    {
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Label'), 1);
        $note = ProcurementNote::firstOrFail();

        foreach (['hr', 'gudang', 'director', 'admin'] as $role) {
            $user = $role === 'gudang' ? $warehouse : User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get(route('procurement-notes.index'))->assertOk();
            $this->actingAs($user)->get(route('procurement-notes.show', $note))->assertOk();
        }

        $employee = User::factory()->create(['role' => 'karyawan']);
        $this->actingAs($employee)->get(route('procurement-notes.index'))->assertForbidden();
    }

    public function test_note_can_be_printed_and_exported_to_excel(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Pulpen'), 4);
        $note = ProcurementNote::firstOrFail();

        $this->actingAs($hr)->get(route('procurement-notes.print', $note))
            ->assertOk()
            ->assertSee($note->number)
            ->assertSee('Pulpen');
        $this->assertNotNull($note->fresh()->last_printed_at);

        $this->actingAs($hr)->get(route('procurement-notes.excel', $note))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename="'.strtolower($note->number).'.xlsx"');
    }

    public function test_note_search_shows_matching_items_with_direct_links(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $joinItem = $this->item('Join Gigi');
        $oilItem = $this->item('Oli Mesin');
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        $matching = $this->requestOn($warehouse, $note, $joinItem, 4, 'Disetujui');
        $this->requestOn($warehouse, $note, $oilItem, 2, 'Menunggu Review');

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'join gigi']))
            ->assertOk()
            ->assertSee('NOTA-20260902')
            ->assertSee('Join Gigi')
            ->assertDontSee('Oli Mesin');

        $response->assertSee(route('procurement-notes.show', $note).'#request-'.$matching->id, false);
    }

    public function test_note_search_matches_number_and_reveals_each_matching_note(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $item = $this->item('Bearing');
        $firstNote = ProcurementNote::findOrCreateForDate('2026-09-02');
        $secondNote = ProcurementNote::findOrCreateForDate('2026-09-07');

        $this->requestOn($warehouse, $firstNote, $item, 3, 'Diterima Penuh');
        $this->requestOn($warehouse, $secondNote, $item, 5, 'Diterima Penuh');

        $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'Bearing']))
            ->assertOk()
            ->assertSee('NOTA-20260902')
            ->assertSee('NOTA-20260907');

        $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'NOTA-20260902']))
            ->assertOk()
            ->assertSee('NOTA-20260902')
            ->assertSee('Nomor Nota cocok')
            ->assertDontSee('NOTA-20260907');
    }

    public function test_note_search_respects_request_status_filter(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $item = $this->item('Bearing');
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        $approved = $this->requestOn($warehouse, $note, $item, 5, 'Disetujui');
        $rejected = $this->requestOn($warehouse, $note, $item, 2, 'Ditolak');

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'Bearing', 'request_status' => 'Disetujui']))
            ->assertOk()
            ->assertSee('NOTA-20260902')
            ->assertSee('#request-'.$approved->id, false);

        $this->assertStringNotContainsString('#request-'.$rejected->id, $response->getContent());
    }

    private function submitRequest(User $warehouse, Item $item, int $quantity): void
    {
        $this->actingAs($warehouse)->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => $quantity,
            'priority' => 'Biasa',
            'reason' => 'Kebutuhan operasional',
        ])->assertRedirect('/gudang/history');
    }

    private function requestOn(User $warehouse, ProcurementNote $note, Item $item, int $quantity, string $status): StockRequest
    {
        return StockRequest::create([
            'user_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Kebutuhan operasional',
            'status' => $status,
            'procurement_note_id' => $note->id,
        ]);
    }

    private function item(string $name): Item
    {
        return Item::create([
            'name' => $name,
            'rack_location' => 'A',
            'stock' => 0,
            'unit' => 'Pcs',
        ]);
    }
}
