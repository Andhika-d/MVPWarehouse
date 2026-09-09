<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test',
            'email' => $role . '-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function makeLocation(string $rack = 'A', int $number = 1): StorageLocation
    {
        $prefix = StorageLocation::getPrefixForRack($rack);

        return StorageLocation::create([
            'code' => $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT),
            'rack' => $rack,
            'number' => $number,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);
    }

    private function makeItem(int $stock = 10, ?StorageLocation $location = null): Item
    {
        $location ??= $this->makeLocation();

        return Item::create([
            'name' => 'Barang Test ' . uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);
    }

    private function makeRequest(User $gudang, Item $item, array $overrides = []): StockRequest
    {
        $request = StockRequest::create(array_merge([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => $item->unit,
            'priority' => 'Biasa',
            'reason' => 'Butuh operasional',
            'status' => 'Menunggu Review',
        ], $overrides));

        if (isset($overrides['created_at'])) {
            $request->forceFill(['created_at' => $overrides['created_at']])->save();
        }

        return $request;
    }

    public function test_delay_sets_status_to_pending(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/delay', ['note' => 'Tunggu anggaran'])
            ->assertRedirect('/hr/approval');

        $request->refresh();
        $this->assertSame('Pending', $request->status);
        $this->assertSame('Tunggu anggaran', $request->review_note);
    }

    public function test_delay_records_history(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/delay', ['note' => 'Tunggu']);

        $this->assertDatabaseHas('request_histories', [
            'stock_request_id' => $request->id,
            'status' => 'Pending',
            'note' => 'Tunggu',
        ]);
    }

    public function test_reject_all_bulk(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $r1 = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 09:00:00']);
        $r2 = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 10:00:00']);

        $this->actingAs($hr)->post('/hr/nota/2026-08-01/reject-all', ['note' => 'Stok tidak tersedia']);

        $this->assertSame('Ditolak', $r1->fresh()->status);
        $this->assertSame('Ditolak', $r2->fresh()->status);
    }

    public function test_delay_all_bulk(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $r1 = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 09:00:00']);
        $r2 = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 10:00:00']);

        $this->actingAs($hr)->post('/hr/nota/2026-08-01/delay-all', ['note' => 'Tunda semua']);

        $this->assertSame('Pending', $r1->fresh()->status);
        $this->assertSame('Pending', $r2->fresh()->status);
    }

    public function test_full_flow_approve_receive_stock_increases(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem(10);
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve', ['note' => 'OK'])
            ->assertRedirect();
        $this->assertSame('Disetujui', $request->fresh()->status);

        $this->actingAs($gudang)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 2,
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame(12, $item->stock);

        $request->refresh();
        $this->assertSame('Diterima Penuh', $request->status);
        $this->assertSame(2, $request->received_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
        ]);
    }

    public function test_partial_receive_then_full(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem(10);
        $request = $this->makeRequest($gudang, $item, ['quantity' => 5]);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve');

        $this->actingAs($gudang)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 3,
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('Sebagian Diterima', $request->status);
        $this->assertSame(3, $request->received_quantity);

        $this->actingAs($gudang)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 2,
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('Diterima Penuh', $request->status);
        $this->assertSame(5, $request->received_quantity);

        $item->refresh();
        $this->assertSame(15, $item->stock);
    }

    public function test_cannot_receive_more_than_remaining(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem(10);
        $request = $this->makeRequest($gudang, $item, ['quantity' => 3]);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve');

        $this->actingAs($gudang)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 5,
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame(0, $request->received_quantity);
        $this->assertSame('Disetujui', $request->status);
    }

    public function test_hr_history_page_loads(): void
    {
        $hr = $this->makeUser('hr');

        $response = $this->actingAs($hr)->get('/hr/history');
        $response->assertOk();
        $response->assertSee('Arsip Historis Pengadaan Barang');
    }

    public function test_hr_history_filter_by_status(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $this->makeRequest($gudang, $item, ['status' => 'Ditolak']);
        $this->makeRequest($gudang, $item, ['status' => 'Disetujui']);

        $response = $this->actingAs($hr)->get('/hr/history?status=Ditolak');
        $response->assertOk();
    }

    public function test_remark_max_length_enforced(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $longNote = str_repeat('A', 1001);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve', ['note' => $longNote])
            ->assertSessionHasErrors('note');
    }

    public function test_export_daftar_belanja_accepts_date_filter(): void
    {
        $hr = $this->makeUser('hr');

        $response = $this->actingAs($hr)->get('/hr/daftar-belanja/export/excel?date=2026-08-07');
        $response->assertOk();
    }

    public function test_delay_route_exists_and_works(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/delay', ['note' => 'Test delay'])
            ->assertRedirect('/hr/approval');

        $this->assertSame('Pending', $request->fresh()->status);
    }

    public function test_hr_can_access_stock_page(): void
    {
        $hr = $this->makeUser('hr');

        $response = $this->actingAs($hr)->get('/hr/stock');
        $response->assertOk();
        $response->assertSee('Stok Barang');
        $response->assertSee('Monitoring Stok Barang');
    }

    public function test_hr_stock_page_uses_hr_base_path(): void
    {
        $hr = $this->makeUser('hr');

        $response = $this->actingAs($hr)->get('/hr/stock');
        $response->assertOk();
        $response->assertSee('/hr/stock');
        $response->assertDontSee('/gudang/stock');
    }

    public function test_hr_stock_detail_rack(): void
    {
        $location = $this->makeLocation('A', 1);
        Item::create([
            'name' => 'Kertas HVS',
            'storage_location_id' => $location->id,
            'stock' => 10,
            'unit' => 'Rim',
        ]);

        $hr = $this->makeUser('hr');

        $response = $this->actingAs($hr)->get('/hr/stock?rack=A');
        $response->assertOk();
        $response->assertSee('Kertas HVS');
    }

    public function test_gudang_stock_page_still_uses_gudang_base_path(): void
    {
        $gudang = $this->makeUser('gudang');

        $response = $this->actingAs($gudang)->get('/gudang/stock');
        $response->assertOk();
        $response->assertSee('/gudang/stock');
    }
}
