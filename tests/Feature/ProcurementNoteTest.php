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

    public function test_request_detail_is_available_in_note_context_and_guards_relation(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Karet Sabuk'), 2);
        $request = StockRequest::firstOrFail();
        $note = $request->procurementNote;

        foreach (['hr', 'gudang', 'director', 'admin'] as $role) {
            $user = $role === 'gudang' ? $warehouse : User::factory()->create(['role' => $role]);
            $this->actingAs($user)
                ->get(route('procurement-notes.requests.show', [$note, $request]))
                ->assertOk()
                ->assertSee('Karet Sabuk')
                ->assertSee('Permintaan Dibuat');
        }

        $otherNote = ProcurementNote::findOrCreateForDate('2026-08-15');
        $this->actingAs($hr)
            ->get(route('procurement-notes.requests.show', [$otherNote, $request]))
            ->assertNotFound();
    }

    public function test_request_detail_shows_full_timeline_and_contextual_hr_actions(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Mur Baut'), 3);
        $request = StockRequest::firstOrFail();
        $note = $request->procurementNote;

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.requests.show', [$note, $request]))
            ->assertOk()
            ->assertSee('Permintaan Dibuat')
            ->assertSee('Terima')
            ->assertSee('Tolak')
            ->assertSee('Tunda');

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve')->assertRedirect('/hr/approval');
        $this->actingAs($warehouse)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 1,
        ])->assertRedirect();

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.requests.show', [$note, $request]))
            ->assertOk()
            ->assertSee('Disetujui HR')
            ->assertSee('Penerimaan Sebagian')
            ->assertSee('Tutup Sisa')
            ->assertDontSee('Terima');

        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Disetujui HR'), strpos($content, 'Permintaan Dibuat'));
        $this->assertLessThan(strpos($content, 'Penerimaan Sebagian'), strpos($content, 'Disetujui HR'));
    }

    public function test_request_detail_falls_back_to_creation_event_without_history(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-03');
        $item = $this->item('Amplas');
        $request = StockRequest::create([
            'user_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Kebutuhan operasional',
            'status' => 'Disetujui',
            'procurement_note_id' => $note->id,
        ]);
        $request->created_at = '2026-09-03 08:00:00';
        $request->save();

        $this->actingAs($hr)
            ->get(route('procurement-notes.requests.show', [$note, $request]))
            ->assertOk()
            ->assertSee('Amplas')
            ->assertSee('Permintaan Dibuat');
    }

    public function test_note_search_and_request_filters_must_match_the_same_request(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        $bearing = $this->requestOn($warehouse, $note, $this->item('Bearing'), 2, 'Ditolak');
        $oil = $this->requestOn($warehouse, $note, $this->item('Oli Mesin'), 3, 'Disetujui');

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'Bearing', 'request_status' => 'Disetujui']))
            ->assertOk()
            ->assertDontSee('NOTA-20260902');

        $content = $response->getContent();
        $this->assertStringNotContainsString('#request-'.$bearing->id, $content);
        $this->assertStringNotContainsString('#request-'.$oil->id, $content);
    }

    public function test_note_search_and_priority_filter_must_match_the_same_request(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        $this->requestOn($warehouse, $note, $this->item('Bearing'), 2, 'Ditolak', 'Biasa');
        $this->requestOn($warehouse, $note, $this->item('Oli Mesin'), 3, 'Disetujui', 'Mendesak');

        $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'Bearing', 'priority' => 'Mendesak']))
            ->assertOk()
            ->assertDontSee('NOTA-20260902');
    }

    public function test_request_detail_hides_approval_panel_before_approval(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        foreach (['Menunggu Review', 'Pending', 'Ditolak'] as $status) {
            $this->requestOn($warehouse, $note, $this->item('Barang '.$status), 1, $status);
        }

        foreach (StockRequest::all() as $request) {
            $this->actingAs($hr)
                ->get(route('procurement-notes.requests.show', [$note, $request]))
                ->assertOk()
                ->assertDontSee('Disetujui oleh')
                ->assertDontSee('Waktu Persetujuan');
        }
    }

    public function test_request_detail_shows_approval_panel_with_reviewer_and_time_when_approved(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');

        $approved = $this->requestOn($warehouse, $note, $this->item('Mur'), 1, 'Disetujui');
        $approved->update(['reviewed_by' => $hr->id, 'approved_at' => now()]);

        $this->travelTo(now()->setDateTime(2026, 9, 10, 14, 30, 0));
        $approved->update(['approved_at' => now()]);

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.requests.show', [$note, $approved]))
            ->assertOk()
            ->assertSee('Disetujui oleh')
            ->assertSee('Waktu Persetujuan')
            ->assertSee('10 Sep 2026, 14:30')
            ->assertSee($hr->name);
    }

    public function test_note_search_still_finds_request_when_item_was_renamed(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $item = $this->item('Bearing Lama');

        $this->travelTo(now()->setDate(2026, 9, 2)->startOfDay()->addHours(8));
        $this->submitRequest($warehouse, $item, 3);
        $request = StockRequest::firstOrFail();
        $note = $request->procurementNote;

        $item->update(['name' => 'Bearing Baru']);

        $response = $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => 'Bearing Lama']))
            ->assertOk()
            ->assertSee('NOTA-20260902');

        $this->assertStringContainsString('#request-'.$request->id, $response->getContent());
    }

    public function test_note_search_treats_percent_and_underscore_literally(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $percentNote = ProcurementNote::findOrCreateForDate('2026-09-02');
        $this->requestOn($warehouse, $percentNote, $this->item('Saklar % Kecil'), 2, 'Disetujui');

        $plainNote = ProcurementNote::findOrCreateForDate('2026-09-03');
        $this->requestOn($warehouse, $plainNote, $this->item('Printer'), 1, 'Ditolak');

        $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => '%']))
            ->assertOk()
            ->assertSee('NOTA-20260902')
            ->assertDontSee('NOTA-20260903');

        $this->actingAs($hr)
            ->get(route('procurement-notes.index', ['search' => '_']))
            ->assertOk()
            ->assertDontSee('NOTA-20260902')
            ->assertDontSee('NOTA-20260903');
    }

    public function test_note_print_defaults_to_landscape_and_supports_portrait(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Pulpen'), 4);
        $note = ProcurementNote::firstOrFail();

        $this->actingAs($hr)
            ->get(route('procurement-notes.print', $note))
            ->assertOk()
            ->assertSee('size: A4 landscape', false)
            ->assertSee('Cetak A4 Landscape')
            ->assertSee('Landscape')
            ->assertSee('Portrait')
            ->assertDontSee('<th>Jumlah</th>', false);

        $this->actingAs($hr)
            ->get(route('procurement-notes.print', [$note, 'orientation' => 'portrait']))
            ->assertOk()
            ->assertSee('size: A4 portrait', false)
            ->assertSee('Cetak A4 Portrait')
            ->assertSee('<th>Jumlah</th>', false)
            ->assertSee('Diminta')
            ->assertSee('Diterima')
            ->assertSee('Sisa');
    }

    public function test_note_print_rejects_invalid_orientation(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Pulpen'), 4);
        $note = ProcurementNote::firstOrFail();

        $this->actingAs($hr)
            ->get(route('procurement-notes.print', [$note, 'orientation' => 'bogus']))
            ->assertRedirect()
            ->assertSessionHasErrors('orientation');
    }

    public function test_period_print_supports_portrait_orientation(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $this->submitRequest($warehouse, $this->item('Pulpen'), 4);

        $this->actingAs($hr)
            ->get(route('procurement-notes.print-period', ['orientation' => 'portrait']))
            ->assertOk()
            ->assertSee('size: A4 portrait', false)
            ->assertSee('Cetak A4 Portrait');
    }

    public function test_note_print_uses_sequential_numbers_and_creation_time(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');
        $first = $this->requestOn($warehouse, $note, $this->item('Bearing'), 4, 'Disetujui');
        $this->requestOn($warehouse, $note, $this->item('Oli Mesin'), 2, 'Disetujui');

        foreach (['landscape', 'portrait'] as $orientation) {
            $response = $this->actingAs($hr)
                ->get(route('procurement-notes.print', [$note, 'orientation' => $orientation]))
                ->assertOk()
                ->assertSee('<th>Waktu</th>', false);

            $content = $response->getContent();

            if ($orientation === 'portrait') {
                $this->assertStringContainsString('<span class="cell-primary">1</span>', $content);
                $this->assertStringContainsString('<span class="cell-primary">2</span>', $content);
                $this->assertStringNotContainsString('<span class="cell-primary">#'.$first->id, $content);
            } else {
                $this->assertStringContainsString('>1</td>', $content);
                $this->assertStringContainsString('>2</td>', $content);
                $this->assertStringNotContainsString('<td>#'.$first->id.'</td>', $content);
            }
        }
    }

    public function test_note_print_shows_remaining_quantity_when_closed_partially(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'gudang']);
        $note = ProcurementNote::findOrCreateForDate('2026-09-02');
        $request = $this->requestOn($warehouse, $note, $this->item('Mur'), 4, 'Ditutup Sebagian');
        $request->update(['received_quantity' => 3]);

        $this->actingAs($hr)
            ->get(route('procurement-notes.print', [$note, 'orientation' => 'portrait']))
            ->assertOk()
            ->assertSee('Diminta 4 Pcs')
            ->assertSee('Diterima 3 Pcs')
            ->assertSee('Sisa 1 Pcs');

        $landscape = $this->actingAs($hr)
            ->get(route('procurement-notes.print', [$note, 'orientation' => 'landscape']))
            ->assertOk();
        $this->assertStringContainsString('>1 Pcs</td>', $landscape->getContent());
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

    private function requestOn(User $warehouse, ProcurementNote $note, Item $item, int $quantity, string $status, string $priority = 'Biasa'): StockRequest
    {
        return StockRequest::create([
            'user_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'unit' => 'Pcs',
            'priority' => $priority,
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
