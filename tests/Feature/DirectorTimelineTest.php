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

class DirectorTimelineTest extends TestCase
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

    private function makeItem(int $stock = 0, ?StorageLocation $location = null): Item
    {
        $location ??= $this->makeLocation();

        return Item::create([
            'name' => 'Barang Test ' . uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);
    }

    private function makeRequest(User $user, Item $item, int $quantity = 4): StockRequest
    {
        return StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => $quantity,
            'received_quantity' => 0,
            'unit' => 'Pcs',
            'priority' => 'Normal',
            'reason' => 'Kebutuhan operasional',
            'status' => 'Menunggu Review',
        ]);
    }

    public function test_request_detail_no_duplicate_for_partial_receipt(): void
    {
        $director = $this->makeUser('director');
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $request = $this->makeRequest($gudang, $item, 4);

        // HR approve
        $request->update(['status' => 'Disetujui', 'reviewed_by' => $hr->id, 'approved_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $hr->id,
            'status' => 'Disetujui',
            'note' => 'Disetujui untuk pembelian',
        ]);

        // Gudang partial receive 2
        $request->increment('received_quantity', 2);
        $request->update(['status' => 'Sebagian Diterima']);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Sebagian Diterima',
            'note' => 'Barang datang sebagian dari supplier',
        ]);
        $item->increment('stock', 2);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => 'Barang datang sebagian dari supplier',
            'occurred_at' => now(),
        ]);

        // Gudang receive remaining 2
        $request->increment('received_quantity', 2);
        $request->update(['status' => 'Diterima Penuh', 'completed_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Diterima Penuh',
            'note' => 'Sisa barang telah diterima',
        ]);
        $item->increment('stock', 2);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => 'Sisa barang telah diterima',
            'occurred_at' => now(),
        ]);

        $this->actingAs($director);

        $response = $this->get("/director/requests/{$request->id}");
        $response->assertStatus(200);

        $content = $response->getContent();

        // Should show combined "Penerimaan Sebagian" with note — NOT separate "Barang Masuk"
        $this->assertStringContainsString('Penerimaan Sebagian', $content);
        $this->assertStringContainsString('Barang datang sebagian dari supplier', $content);

        // Should show combined "Penerimaan Penuh" with note
        $this->assertStringContainsString('Penerimaan Penuh', $content);
        $this->assertStringContainsString('Sisa barang telah diterima', $content);

        // Should NOT have raw "Barang Masuk" entries (they are deduplicated into receipt events)
        $this->assertStringNotContainsString('Barang Masuk</span>', $content);
    }

    public function test_request_detail_shows_cumulative_quantity(): void
    {
        $director = $this->makeUser('director');
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $request = $this->makeRequest($gudang, $item, 10);

        // HR approve
        $request->update(['status' => 'Disetujui', 'reviewed_by' => $hr->id, 'approved_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $hr->id,
            'status' => 'Disetujui',
            'note' => null,
        ]);

        // First partial: 3 of 10
        $request->increment('received_quantity', 3);
        $request->update(['status' => 'Sebagian Diterima']);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Sebagian Diterima',
            'note' => null,
        ]);
        $item->increment('stock', 3);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => null,
            'occurred_at' => now(),
        ]);

        // Second partial: 4 of 10
        $request->increment('received_quantity', 4);
        $request->update(['status' => 'Sebagian Diterima']);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Sebagian Diterima',
            'note' => null,
        ]);
        $item->increment('stock', 4);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 4,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => null,
            'occurred_at' => now(),
        ]);

        // Third partial: 3 of 10 (full now)
        $request->increment('received_quantity', 3);
        $request->update(['status' => 'Diterima Penuh', 'completed_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Diterima Penuh',
            'note' => null,
        ]);
        $item->increment('stock', 3);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => null,
            'occurred_at' => now(),
        ]);

        $this->actingAs($director);

        $response = $this->get("/director/requests/{$request->id}");
        $response->assertStatus(200);

        $content = $response->getContent();

        // All three receipt events should appear
        $this->assertStringContainsString('Penerimaan Sebagian', $content);

        // Cumulative tracking: first=3, second=7, third=10
        $this->assertStringContainsString('total: 3', $content);
        $this->assertStringContainsString('total: 7', $content);
        $this->assertStringContainsString('total: 10', $content);

        // No raw "Barang Masuk" events
        $this->assertStringNotContainsString('Barang Masuk</span>', $content);
    }

    public function test_request_detail_alasan_request_appears(): void
    {
        $director = $this->makeUser('director');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $request = $this->makeRequest($gudang, $item, 5);

        $this->actingAs($director);

        $response = $this->get("/director/requests/{$request->id}");
        $response->assertStatus(200);

        $content = $response->getContent();

        // The "Request Dibuat" event should contain the reason
        $this->assertStringContainsString('Kebutuhan operasional', $content);
    }

    public function test_global_timeline_no_duplicate_receipt(): void
    {
        $director = $this->makeUser('director');
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $request = $this->makeRequest($gudang, $item, 4);

        // Approve
        $request->update(['status' => 'Disetujui', 'reviewed_by' => $hr->id, 'approved_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $hr->id,
            'status' => 'Disetujui',
            'note' => null,
        ]);

        // Partial receive 2
        $request->increment('received_quantity', 2);
        $request->update(['status' => 'Sebagian Diterima']);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Sebagian Diterima',
            'note' => 'Barang datang sebagian',
        ]);
        $item->increment('stock', 2);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => 'Barang datang sebagian',
            'occurred_at' => now(),
        ]);

        // Full receive 2
        $request->increment('received_quantity', 2);
        $request->update(['status' => 'Diterima Penuh', 'completed_at' => now()]);
        $request->requestHistories()->create([
            'user_id' => $gudang->id,
            'status' => 'Diterima Penuh',
            'note' => 'Sisa diterima',
        ]);
        $item->increment('stock', 2);
        $item->refresh();
        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $gudang->id,
            'balance_after' => $item->stock,
            'note' => 'Sisa diterima',
            'occurred_at' => now(),
        ]);

        $this->actingAs($director);

        $response = $this->get('/director/timeline');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Should show combined receipt events
        $this->assertStringContainsString('Penerimaan Sebagian', $content);
        $this->assertStringContainsString('Barang datang sebagian', $content);
        $this->assertStringContainsString('Penerimaan Penuh', $content);
        $this->assertStringContainsString('Sisa diterima', $content);

        // Should NOT have standalone "Barang Masuk" for receipt-linked movements
        $this->assertStringNotContainsString('Barang Masuk</span>', $content);
    }
}
