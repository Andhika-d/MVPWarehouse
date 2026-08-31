<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartialCloseTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test',
            'email' => $role . '-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
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

    private function makeItem(int $stock = 0, ?StorageLocation $location = null): Item
    {
        $location ??= $this->makeLocation();

        return Item::create([
            'name' => 'Barang Close ' . uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);
    }

    private function makeApprovedRequest(User $user, Item $item, int $quantity = 10, int $received = 0): StockRequest
    {
        $request = StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => $quantity,
            'received_quantity' => $received,
            'unit' => 'Pcs',
            'priority' => 'Normal',
            'reason' => 'Kebutuhan operasional',
            'status' => $received > 0 ? 'Sebagian Diterima' : 'Disetujui',
        ]);

        $request->requestHistories()->create([
            'user_id' => $user->id,
            'status' => 'Disetujui',
            'note' => 'Disetujui untuk pembelian',
        ]);

        if ($received > 0) {
            $request->requestHistories()->create([
                'user_id' => $user->id,
                'status' => 'Sebagian Diterima',
                'note' => 'Barang datang sebagian dari supplier',
            ]);
        }

        return $request;
    }

    public function test_gudang_can_close_partial_remaining(): void
    {
        $gudang = $this->makeUser('gudang');
        $gudangStore = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);
        $stockBefore = $item->refresh()->stock;

        $this->actingAs($gudangStore);

        $response = $this->post('/gudang/penerimaan/close', [
            'stock_request_id' => $request->id,
            'note' => 'Supplier tidak dapat memenuhi sisa pesanan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();

        $this->assertSame('Ditutup Sebagian', $request->status);
        $this->assertNotNull($request->closed_at);
        $this->assertSame($gudangStore->id, $request->closed_by);
        $this->assertSame('Supplier tidak dapat memenuhi sisa pesanan', $request->close_note);
        $this->assertNull($request->completed_at);

        $this->assertDatabaseHas('request_histories', [
            'stock_request_id' => $request->id,
            'user_id' => $gudangStore->id,
            'status' => 'Ditutup Sebagian',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $gudangStore->id,
            'action' => 'closed_request',
            'target_id' => $request->id,
        ]);

        // No stock change from closing
        $this->assertSame($stockBefore, $item->refresh()->stock);
    }

    public function test_hr_can_close_partial_remaining(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);
        $stockBefore = $item->refresh()->stock;

        $this->actingAs($hr);

        $response = $this->post("/hr/requests/{$request->id}/close", [
            'note' => 'Barang diganti dengan produk lain',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $request->refresh();

        $this->assertSame('Ditutup Sebagian', $request->status);
        $this->assertSame($hr->id, $request->closed_by);
        $this->assertSame('Barang diganti dengan produk lain', $request->close_note);
        $this->assertSame($stockBefore, $item->refresh()->stock);
    }

    public function test_close_zero_received_sets_dibatalkan(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 5, 0);

        $this->actingAs($hr);

        $response = $this->post("/hr/requests/{$request->id}/close", [
            'note' => 'Kebutuhan sudah tidak diperlukan',
        ]);

        $response->assertSessionHas('success');

        $this->assertSame('Dibatalkan', $request->refresh()->status);
        $this->assertNotNull($request->closed_at);
    }

    public function test_close_requires_note(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 5, 2);

        $this->actingAs($hr);

        $response = $this->from('/hr/history')->post("/hr/requests/{$request->id}/close");

        $response->assertSessionHasErrors('note');
        $this->assertSame('Sebagian Diterima', $request->refresh()->status);
    }

    public function test_close_rejects_whitespace_note(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 5, 2);

        $this->actingAs($hr);

        // TrimStrings middleware turns '   ' into '', so required validation blocks it
        $response = $this->from('/hr/history')->post("/hr/requests/{$request->id}/close", [
            'note' => '   ',
        ]);

        $response->assertSessionHasErrors('note');
        $this->assertSame('Sebagian Diterima', $request->refresh()->status);

        // Direct model call is also guarded against whitespace-only notes
        $result = $request->closeRemaining($hr->id, '   ');
        $this->assertFalse($result['success']);
        $this->assertSame('Sebagian Diterima', $request->refresh()->status);
    }

    public function test_second_close_attempt_is_rejected(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $hr2 = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);

        // HR closes first (first one wins)
        $this->actingAs($hr);
        $this->post("/hr/requests/{$request->id}/close", ['note' => 'Supplier tidak memenuhi']);

        // Second closer (gudang) gets a clear message
        $this->actingAs($gudang);
        $response = $this->from('/gudang/penerimaan')->post('/gudang/penerimaan/close', [
            'stock_request_id' => $request->id,
            'note' => 'Coba tutup ulang',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('sudah ditutup oleh', session('error'));
        $this->assertStringContainsString($hr->name, session('error'));

        $this->assertSame('Ditutup Sebagian', $request->refresh()->status);
        $this->assertSame($hr->id, $request->closed_by);
    }

    public function test_closed_request_removed_from_penerimaan_list(): void
    {
        $gudang = $this->makeUser('gudang');
        $gudangStore = $this->makeUser('gudang');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);

        $this->actingAs($gudangStore);
        $this->post('/gudang/penerimaan/close', [
            'stock_request_id' => $request->id,
            'note' => 'Supplier tidak dapat memenuhi sisa pesanan',
        ]);

        $response = $this->get('/gudang/penerimaan');
        $response->assertStatus(200);
        $response->assertDontSee($item->name);

        // Requester (owner) can still see the closure info in the detail page
        $this->actingAs($gudang);
        $responseDetail = $this->get('/gudang/history/' . $request->id);
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee($request->close_note);
    }

    public function test_director_request_detail_shows_close_event(): void
    {
        $director = $this->makeUser('director');
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);

        $this->actingAs($hr);
        $this->post("/hr/requests/{$request->id}/close", ['note' => 'Supplier tutup toko']);

        $this->actingAs($director);

        $response = $this->get("/director/requests/{$request->id}");
        $response->assertStatus(200);
        $response->assertSee('Sisa Request Ditutup');
        $response->assertSee('Supplier tutup toko');
        $response->assertSee('Ditutup Sebagian');
    }

    public function test_director_global_timeline_shows_close_event(): void
    {
        $director = $this->makeUser('director');
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeApprovedRequest($gudang, $item, 10, 6);
        $item->increment('stock', 6);

        $this->actingAs($hr);
        $this->post("/hr/requests/{$request->id}/close", ['note' => 'Supplier tutup toko']);

        $this->actingAs($director);

        $response = $this->get('/director/timeline');
        $response->assertStatus(200);
        $response->assertSee('Sisa Request Ditutup');
        $response->assertSee('Supplier tutup toko');
    }
}