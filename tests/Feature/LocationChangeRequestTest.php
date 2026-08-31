<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocationChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, string $suffix = ''): User
    {
        return User::create([
            'name' => ucfirst($role).' User'.$suffix,
            'email' => $role.$suffix.'-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
        ]);
    }

    protected function makeLocation(string $rack, int $number, ?string $subLocation = null): StorageLocation
    {
        $prefix = StorageLocation::getPrefixForRack($rack);

        return StorageLocation::create([
            'code' => $prefix.'-'.str_pad($number, 3, '0', STR_PAD_LEFT),
            'rack' => $rack,
            'number' => $number,
            'sub_location' => $subLocation,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);
    }

    protected function makeItem(StorageLocation $location, int $stock = 10): Item
    {
        $item = Item::create([
            'name' => 'Barang Lokasi '.uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);

        $location->syncStatus();

        return $item;
    }

    protected function makeRequest(User $gudang, Item $item, StorageLocation $to): LocationChangeRequest
    {
        $from = $item->storageLocation;

        return LocationChangeRequest::create([
            'item_id' => $item->id,
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'from_sub_location' => $from->sub_location,
            'target_sub_location' => $to->sub_location,
            'requested_by' => $gudang->id,
            'status' => LocationChangeRequest::STATUS_PENDING,
            'reason' => 'Butuh penyesuaian lokasi',
        ]);
    }

    protected function makeLegacyRequest(User $gudang, Item $item, StorageLocation $to): LocationChangeRequest
    {
        return LocationChangeRequest::create([
            'item_id' => $item->id,
            'from_location_id' => $item->storage_location_id,
            'to_location_id' => $to->id,
            'requested_by' => $gudang->id,
            'status' => LocationChangeRequest::STATUS_PENDING,
            'reason' => 'Pemindahan lama',
        ]);
    }

    public function test_gudang_location_change_page_renders_with_items(): void
    {
        $gudang = $this->makeUser('gudang');
        $loc = $this->makeLocation('A', 1, 'A.1.1.1');
        $this->makeItem($loc);

        $this->actingAs($gudang)
            ->get('/gudang/location-change')
            ->assertOk()
            ->assertSee('Slot Tujuan')
            ->assertSee('A.1.1.1');
    }

    public function test_slot_search_returns_naturally_sorted_slot_objects(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeLocation('A', 1, 'A.1.1.1');
        $this->makeLocation('A', 2, 'A.1.1.10');
        $this->makeLocation('A', 3, 'A.1.1.2');
        $this->makeLocation('B', 1, 'B.1.1.1');
        $this->makeLocation('Z', 1, 'Z.1.1.1');

        $response = $this->actingAs($gudang)
            ->get('/gudang/location-change/search?q=1.1')
            ->assertOk()
            ->assertJsonCount(5);

        $subLocations = array_column($response->json(), 'sub_location');

        $this->assertSame(['A.1.1.1', 'A.1.1.2', 'A.1.1.10', 'B.1.1.1', 'Z.1.1.1'], $subLocations);

        $first = $response->json()[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('rack', $first);
        $this->assertArrayHasKey('status', $first);
        $this->assertArrayHasKey('items_count', $first);
    }

    public function test_slot_search_matches_normalized_inputs_like_423_to_A423(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeLocation('A', 1, 'A.4.2.3');
        $this->makeLocation('A', 2, 'A.4.2.4');
        $this->makeLocation('B', 1, 'B.1.5.1');

        $response = $this->actingAs($gudang)
            ->get('/gudang/location-change/search?q=423')
            ->assertOk()
            ->assertJsonCount(1);

        $this->assertSame('A.4.2.3', $response->json()[0]['sub_location']);
    }

    public function test_slot_search_requires_at_least_two_characters(): void
    {
        $gudang = $this->makeUser('gudang');
        $this->makeLocation('A', 1, 'A.1.1.1');

        $this->actingAs($gudang)
            ->get('/gudang/location-change/search?q=A')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_slot_search_limits_results_to_twenty(): void
    {
        $gudang = $this->makeUser('gudang');

        for ($i = 1; $i <= 25; $i++) {
            $this->makeLocation('B', $i + 100, 'B.9.1.'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($gudang)
            ->get('/gudang/location-change/search?q=9.1')
            ->assertOk()
            ->assertJsonCount(20);

        $this->assertSame('B.9.1.01', $response->json()[0]['sub_location']);
    }

    public function test_gudang_can_submit_slot_move(): void
    {
        $gudang = $this->makeUser('gudang');
        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $item = $this->makeItem($from);

        $this->actingAs($gudang)
            ->post('/gudang/location-change', [
                'item_id' => $item->id,
                'target_location_id' => $to->id,
                'reason' => 'Dekat pintu keluar',
            ])
            ->assertRedirect();

        $change = LocationChangeRequest::first();

        $this->assertNotNull($change);
        $this->assertSame($item->id, $change->item_id);
        $this->assertSame($from->id, $change->from_location_id);
        $this->assertSame($to->id, $change->to_location_id);
        $this->assertSame('A.1.1.1', $change->from_sub_location);
        $this->assertSame('B.1.1.1', $change->target_sub_location);
        $this->assertSame(LocationChangeRequest::STATUS_PENDING, $change->status);
        $this->assertTrue($change->isSubLocationChange());

        $from->refresh();
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $from->status);
        $this->assertSame($from->id, $item->refresh()->storage_location_id, 'Barang belum dipindah sebelum disetujui.');
    }

    public function test_submit_same_slot_is_rejected(): void
    {
        $gudang = $this->makeUser('gudang');
        $loc = $this->makeLocation('A', 1, 'A.1.1.1');
        $item = $this->makeItem($loc);

        $this->actingAs($gudang)
            ->post('/gudang/location-change', [
                'item_id' => $item->id,
                'target_location_id' => $loc->id,
                'reason' => 'Coba saja',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, LocationChangeRequest::count());
    }

    public function test_submit_requires_target_location_id(): void
    {
        $gudang = $this->makeUser('gudang');
        $loc = $this->makeLocation('A', 1, 'A.1.1.1');
        $item = $this->makeItem($loc);

        $this->actingAs($gudang)
            ->post('/gudang/location-change', [
                'item_id' => $item->id,
                'reason' => 'Coba saja',
            ])
            ->assertSessionHasErrors('target_location_id');
    }

    public function test_submit_bypasses_item_without_location(): void
    {
        $gudang = $this->makeUser('gudang');
        $to = $this->makeLocation('A', 1, 'A.1.1.1');
        $item = Item::create([
            'name' => 'Barang Tanpa Lokasi',
            'storage_location_id' => null,
            'stock' => 5,
            'unit' => 'Pcs',
        ]);

        $this->actingAs($gudang)
            ->post('/gudang/location-change', [
                'item_id' => $item->id,
                'target_location_id' => $to->id,
                'reason' => 'Coba saja',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, LocationChangeRequest::count());
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $gudang = $this->makeUser('gudang');
        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $other = $this->makeLocation('C', 1, 'C.1.1.1');
        $item = $this->makeItem($from);

        $this->makeRequest($gudang, $item, $to);

        $this->actingAs($gudang)
            ->post('/gudang/location-change', [
                'item_id' => $item->id,
                'target_location_id' => $other->id,
                'reason' => 'Coba lagi',
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, LocationChangeRequest::count());
    }

    public function test_admin_approve_moves_item_to_empty_slot(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $item = $this->makeItem($from);
        $change = $this->makeRequest($gudang, $item, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve', ['action' => 'swap'])
            ->assertSessionHas('success');

        $from->refresh();
        $to->refresh();
        $item->refresh();
        $change->refresh();

        $this->assertSame($to->id, $item->storage_location_id, 'Barang dipindah ke slot tujuan.');
        $this->assertSame(StorageLocation::STATUS_EMPTY, $from->status);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $to->status);
        $this->assertNull($change->resolution_action);
        $this->assertNull($change->swap_item_id);
        $this->assertSame('A.1.1.1', $from->sub_location, 'Sub Lokasi asal tidak berubah.');
        $this->assertSame('B.1.1.1', $to->sub_location, 'Sub Lokasi tujuan tidak berubah.');
        $this->assertSame(LocationChangeRequest::STATUS_APPROVED, $change->status);
        $this->assertSame($admin->id, $change->approved_by);

        $this->assertSame(1, AuditLog::where('action', 'approved_location_change')->count());
        $this->assertStringContainsString('pemindahan', AuditLog::latest()->first()->details);
    }

    public function test_admin_approve_swaps_places_when_target_occupied(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $itemA = $this->makeItem($from, 2);
        $itemB = $this->makeItem($to, 3);
        $change = $this->makeRequest($gudang, $itemA, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve', ['action' => 'swap', 'swap_item_id' => $itemB->id])
            ->assertSessionHas('success');

        $itemA->refresh();
        $itemB->refresh();
        $from->refresh();
        $to->refresh();
        $change->refresh();

        $this->assertSame($to->id, $itemA->storage_location_id);
        $this->assertSame($from->id, $itemB->storage_location_id);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $from->status);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $to->status);
        $this->assertSame('swap', $change->resolution_action);
        $this->assertSame($itemB->id, $change->swap_item_id);
        $this->assertSame(LocationChangeRequest::STATUS_APPROVED, $change->status);

        $this->assertStringContainsString('pertukaran', AuditLog::latest()->first()->details);
    }

    public function test_admin_approve_swap_requires_selected_swap_item(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $itemA = $this->makeItem($from);
        $itemB = $this->makeItem($to);
        $change = $this->makeRequest($gudang, $itemA, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve', ['action' => 'swap'])
            ->assertSessionHas('error');

        $this->assertSame($from->id, $itemA->refresh()->storage_location_id, 'Tidak ada barang yang dipindah.');
        $this->assertSame($to->id, $itemB->refresh()->storage_location_id);
        $this->assertTrue($change->refresh()->isPending());
    }

    public function test_admin_approve_stacks_items_when_target_occupied(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $itemA = $this->makeItem($from, 2);
        $itemB = $this->makeItem($to, 3);
        $change = $this->makeRequest($gudang, $itemA, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve', ['action' => 'stack'])
            ->assertSessionHas('success');

        $itemA->refresh();
        $itemB->refresh();
        $from->refresh();
        $to->refresh();
        $change->refresh();

        $this->assertSame($to->id, $itemA->storage_location_id);
        $this->assertSame($to->id, $itemB->storage_location_id, 'Barang tujuan tetap di slot tujuan.');
        $this->assertSame(StorageLocation::STATUS_EMPTY, $from->status);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $to->status);
        $this->assertSame('stack', $change->resolution_action);
        $this->assertNull($change->swap_item_id);
        $this->assertSame(LocationChangeRequest::STATUS_APPROVED, $change->status);

        $this->assertStringContainsString('penumpukan', AuditLog::latest()->first()->details);
    }

    public function test_admin_approve_auto_cancelled_when_item_moved(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $other = $this->makeLocation('C', 1, 'C.1.1.1');
        $item = $this->makeItem($from);
        $change = $this->makeRequest($gudang, $item, $to);

        $item->update(['storage_location_id' => $other->id]);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve')
            ->assertSessionHas('error');

        $change->refresh();
        $this->assertSame(LocationChangeRequest::STATUS_APPROVED, $change->status, 'Dibatalkan otomatis.');
        $this->assertNotNull($change->admin_note);
    }

    public function test_admin_reject_leaves_slot_unchanged(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, 'A.1.1.1');
        $to = $this->makeLocation('B', 1, 'B.1.1.1');
        $item = $this->makeItem($from);
        $change = $this->makeRequest($gudang, $item, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/reject', ['admin_note' => 'Tidak disetujui'])
            ->assertSessionHas('success');

        $from->refresh();
        $change->refresh();

        $this->assertSame($from->id, $item->refresh()->storage_location_id);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $from->status);
        $this->assertSame(LocationChangeRequest::STATUS_REJECTED, $change->status);
        $this->assertSame('Tidak disetujui', $change->admin_note);
    }

    public function test_legacy_swap_change_still_works(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, null);
        $to = $this->makeLocation('B', 1, null);
        $itemA = $this->makeItem($from, 2);
        $itemB = $this->makeItem($to, 3);
        $change = $this->makeLegacyRequest($gudang, $itemA, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve')
            ->assertSessionHas('success');

        $itemA->refresh();
        $itemB->refresh();
        $from->refresh();
        $to->refresh();
        $change->refresh();

        $this->assertSame($to->id, $itemA->storage_location_id);
        $this->assertSame($from->id, $itemB->storage_location_id);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $from->status);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $to->status);
        $this->assertSame('swap', $change->resolution_action);
        $this->assertSame($itemB->id, $change->swap_item_id);
        $this->assertSame(LocationChangeRequest::STATUS_APPROVED, $change->status);
        $this->assertFalse($change->isSubLocationChange());
    }

    public function test_legacy_move_to_empty_slot_still_works(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');

        $from = $this->makeLocation('A', 1, null);
        $to = $this->makeLocation('B', 1, null);
        $itemA = $this->makeItem($from, 2);
        $change = $this->makeLegacyRequest($gudang, $itemA, $to);

        $this->actingAs($admin)
            ->post('/admin/location-changes/'.$change->id.'/approve')
            ->assertSessionHas('success');

        $itemA->refresh();
        $from->refresh();
        $to->refresh();

        $this->assertSame($to->id, $itemA->storage_location_id);
        $this->assertSame(StorageLocation::STATUS_EMPTY, $from->status);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $to->status);
    }
}