<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\RequestHistory;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\OtherRequestNotification;
use App\Support\NotificationText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtherRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function makeItem(): Item
    {
        $location = StorageLocation::create([
            'code' => 'RA-001',
            'rack' => 'A',
            'number' => 1,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);

        return Item::create([
            'name' => 'Kertas HVS A4',
            'storage_location_id' => $location->id,
            'stock' => 5,
            'unit' => 'Rim',
            'primary_unit' => 'Rim',
        ]);
    }

    private function makeOtherRequest(User $gudang, array $overrides = []): StockRequest
    {
        return StockRequest::create(array_merge([
            'user_id' => $gudang->id,
            'item_id' => null,
            'item_name' => 'Tali Webbing',
            'request_kind' => StockRequest::KIND_OTHER,
            'item_size' => '5 cm',
            'quantity' => 4,
            'quantity_in_primary' => 4,
            'received_quantity' => 0,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'status' => 'Menunggu Review',
        ], $overrides));
    }

    public function test_gudang_can_submit_other_request_with_manual_details(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($gudang)->post('/gudang/request-barang', [
            'request_kind' => StockRequest::KIND_OTHER,
            'item_name' => 'Tali Webbing',
            'item_size' => '5 cm',
            'quantity' => 3,
            'unit' => 'Roll',
            'priority' => 'Biasa',
            'reason' => 'Tidak tersedia di rak',
        ]);

        $response->assertRedirect('/gudang/history');

        $request = StockRequest::firstOrFail();
        $this->assertSame(StockRequest::KIND_OTHER, $request->request_kind);
        $this->assertTrue($request->isOther());
        $this->assertNull($request->item_id);
        $this->assertSame('Tali Webbing', $request->item_name);
        $this->assertSame('5 cm', $request->item_size);
        $this->assertSame('Roll', $request->unit);
        $this->assertEquals(3, $request->quantity_in_primary);
        $this->assertSame('Tali Webbing (5 cm)', $request->displayName());

        $this->assertTrue($hr->notifications()->where('type', NewRequestNotification::class)->exists());
        $this->assertTrue($admin->notifications()->where('type', OtherRequestNotification::class)->exists());

        $audit = AuditLog::where('action', 'created_request')->firstOrFail();
        $this->assertStringContainsString('(Lainnya)', $audit->details);
    }

    public function test_master_request_does_not_notify_admin_and_keeps_master_kind(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $item = $this->makeItem();

        $this->actingAs($gudang)->post('/gudang/request-barang', [
            'request_kind' => StockRequest::KIND_MASTER,
            'item_id' => $item->id,
            'quantity' => 2,
            'priority' => 'Biasa',
        ])->assertRedirect('/gudang/history');

        $request = StockRequest::firstOrFail();
        $this->assertSame(StockRequest::KIND_MASTER, $request->request_kind);
        $this->assertTrue($request->isMaster());
        $this->assertFalse($admin->notifications()->where('type', OtherRequestNotification::class)->exists());
    }

    public function test_other_request_rejects_unit_outside_item_units(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->actingAs($gudang)->post('/gudang/request-barang', [
            'request_kind' => StockRequest::KIND_OTHER,
            'item_name' => 'Tali Webbing',
            'quantity' => 1,
            'unit' => 'Galon',
            'priority' => 'Biasa',
        ])->assertSessionHasErrors('unit');

        $this->assertSame(0, StockRequest::count());
    }

    public function test_other_request_cannot_be_tied_to_master_item(): void
    {
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem();

        $this->actingAs($gudang)->post('/gudang/request-barang', [
            'request_kind' => StockRequest::KIND_OTHER,
            'item_id' => $item->id,
            'item_name' => 'Tali Webbing',
            'quantity' => 1,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
        ])->assertSessionHasErrors('item_name');
    }

    public function test_other_request_can_be_approved_and_received_without_stock_movement(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $request = $this->makeOtherRequest($gudang);

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve', [
            'note' => 'Disetujui',
        ])->assertRedirect();

        $this->assertSame('Disetujui', $request->fresh()->status);

        $this->actingAs($gudang)->post('/gudang/penerimaan', [
            'stock_request_id' => $request->id,
            'received_quantity' => 4,
            'note' => 'Barang datang',
        ])->assertRedirect();

        $request->refresh();
        $this->assertEquals(4, $request->received_quantity);
        $this->assertSame('Diterima Penuh', $request->status);
        $this->assertSame(0, StockMovement::count());

        $this->assertTrue($request->requestHistories()
            ->where('note', 'like', '%belum masuk rak%')
            ->exists());
        $this->assertTrue(AuditLog::where('action', 'received_other_request')
            ->where('target_id', $request->id)
            ->exists());
    }

    public function test_admin_can_promote_other_request_to_existing_empty_slot(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $request = $this->makeOtherRequest($gudang, [
            'status' => 'Diterima Penuh',
            'received_quantity' => 7,
        ]);

        $location = StorageLocation::create([
            'code' => 'RD-900',
            'rack' => 'D',
            'number' => 900,
            'sub_location' => 'D9.9.9',
            'status' => StorageLocation::STATUS_EMPTY,
        ]);

        $this->actingAs($admin)->post(route('admin.other-requests.promote', $request), [
            'storage_location_id' => $location->id,
        ])->assertRedirect(route('admin.other-requests.index'));

        $request->refresh();
        $this->assertNotNull($request->item_id);
        $this->assertNotNull($request->promoted_at);
        $this->assertSame($admin->id, $request->promoted_by);
        $this->assertSame(StockRequest::KIND_OTHER, $request->request_kind);
        $this->assertSame('Tali Webbing', $request->item_name);

        $item = Item::findOrFail($request->item_id);
        $this->assertSame('Tali Webbing', $item->name);
        $this->assertSame('5 cm', $item->size);
        $this->assertEquals(7, $item->stock);
        $this->assertSame('Pcs', $item->primary_unit);
        $this->assertSame($location->id, $item->storage_location_id);

        $this->assertTrue($item->itemUnits()->where('unit_name', 'Pcs')->where('is_primary', true)->exists());
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $location->fresh()->status);

        $movement = StockMovement::where('stock_request_id', $request->id)->firstOrFail();
        $this->assertSame(StockMovement::TYPE_ADJUSTMENT, $movement->type);
        $this->assertEquals(7, $movement->balance_after);

        $this->assertTrue($request->requestHistories()->where('note', 'like', '%didaftarkan ke rak%')->exists());
        $this->assertTrue(AuditLog::where('action', 'promoted_other_request')
            ->where('target_id', $request->id)
            ->exists());
    }

    public function test_admin_can_promote_other_request_to_new_sub_location_without_received_stock(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $request = $this->makeOtherRequest($gudang, [
            'unit' => 'Box',
            'quantity' => 2,
            'quantity_in_primary' => 2,
        ]);

        $this->actingAs($admin)->post(route('admin.other-requests.promote', $request), [
            'rack' => 'D',
            'sub_location' => 'D5.5.1',
        ])->assertRedirect(route('admin.other-requests.index'));

        $location = StorageLocation::where('sub_location', 'D5.5.1')->firstOrFail();
        $this->assertSame('D', $location->rack);
        $this->assertSame(StorageLocation::STATUS_OCCUPIED, $location->status);
        $this->assertStringStartsWith('RD-', $location->code);

        $request->refresh();
        $this->assertSame(StockRequest::KIND_OTHER, $request->request_kind);
        $item = Item::findOrFail($request->item_id);
        $this->assertEquals(0, $item->stock);
        $this->assertSame('Box', $item->primary_unit);
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(1, ItemUnit::where('item_id', $item->id)->count());
    }

    public function test_promotion_is_idempotent(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $request = $this->makeOtherRequest($gudang, ['received_quantity' => 1]);
        $location = StorageLocation::create([
            'code' => 'RD-901',
            'rack' => 'D',
            'number' => 901,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);

        $this->actingAs($admin)->post(route('admin.other-requests.promote', $request), [
            'storage_location_id' => $location->id,
        ])->assertRedirect(route('admin.other-requests.index'));

        $this->actingAs($admin)->post(route('admin.other-requests.promote', $request), [
            'storage_location_id' => $location->id,
        ])->assertSessionHas('error', __('Permintaan ini sudah didaftarkan ke rak.'));

        $this->assertSame(1, Item::count());
        $this->assertSame(1, AuditLog::where('action', 'promoted_other_request')->count());
    }

    public function test_other_requests_remain_visible_with_label_after_promotion(): void
    {
        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $other = $this->makeOtherRequest($gudang, ['status' => 'Diterima Penuh', 'received_quantity' => 2]);
        $masterItem = $this->makeItem();
        StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $masterItem->id,
            'item_name' => $masterItem->name,
            'request_kind' => StockRequest::KIND_MASTER,
            'quantity' => 1,
            'quantity_in_primary' => 1,
            'unit' => 'Rim',
            'priority' => 'Biasa',
            'status' => 'Menunggu Review',
        ]);

        $this->actingAs($admin)->post(route('admin.other-requests.promote', $other), [
            'rack' => 'D',
            'sub_location' => 'D5.5.2',
        ])->assertRedirect(route('admin.other-requests.index'));

        $this->actingAs($admin)->get(route('admin.other-requests.index'))
            ->assertOk()
            ->assertSee('Tali Webbing')
            ->assertSee('Lainnya')
            ->assertSee('Sudah terdaftar')
            ->assertDontSee('Kertas HVS A4');

        $this->actingAs($gudang)->get('/gudang/history')
            ->assertOk()
            ->assertSee('Tali Webbing')
            ->assertSee('Lainnya');
    }

    public function test_other_request_labels_and_notification_translate_to_english(): void
    {
        app()->setLocale('en');

        $gudang = $this->makeUser('gudang');
        $admin = $this->makeUser('admin');
        $request = $this->makeOtherRequest($gudang);

        $this->actingAs($admin)->get(route('admin.other-requests.index'))
            ->assertOk()
            ->assertSee('Other Items')
            ->assertSee('Requests outside racks A–E')
            ->assertSee('Others')
            ->assertDontSee('Barang Lainnya');

        $this->actingAs($gudang)->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Other (outside racks)')
            ->assertSee('Items in Racks A–E');

        $notification = (new OtherRequestNotification($request))->toArray($admin);
        $text = NotificationText::resolve($notification);
        $this->assertSame('New Other Item Request', $text['title']);
        $this->assertStringContainsString('outside the racks', $text['message']);
    }
}
