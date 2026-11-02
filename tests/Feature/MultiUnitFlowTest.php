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

class MultiUnitFlowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function item(int $stock = 100): Item
    {
        $location = StorageLocation::create([
            'code' => 'MU-001',
            'rack' => 'A',
            'number' => 1,
            'status' => StorageLocation::STATUS_OCCUPIED,
        ]);

        $item = Item::create([
            'name' => 'Multi Unit Item',
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
            'primary_unit' => 'Pcs',
        ]);

        $item->itemUnits()->create([
            'unit_name' => 'Pcs',
            'conversion_to_primary' => 1,
            'is_primary' => true,
            'context' => 'both',
        ]);

        $item->itemUnits()->create([
            'unit_name' => 'Pack',
            'conversion_to_primary' => 200,
            'is_primary' => false,
            'context' => 'both',
        ]);

        return $item;
    }

    public function test_admin_can_add_and_remove_unit_conversions(): void
    {
        $admin = $this->user('admin');
        $item = $this->item();

        $this->actingAs($admin)
            ->postJson("/admin/items/{$item->id}/units", [
                'unit_name' => 'Box',
                'conversion_to_primary' => 50,
                'context' => 'usage',
            ])
            ->assertOk()
            ->assertJson(['message' => __('Unit berhasil ditambahkan')]);

        $this->assertDatabaseHas('item_units', [
            'item_id' => $item->id,
            'unit_name' => 'Box',
            'is_primary' => false,
            'context' => 'usage',
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/items/{$item->id}/units", [
                'unit_name' => 'Box',
                'conversion_to_primary' => 10,
                'context' => 'usage',
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->deleteJson("/admin/items/{$item->id}/units/Box")
            ->assertOk();

        $this->assertDatabaseMissing('item_units', [
            'item_id' => $item->id,
            'unit_name' => 'Box',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/admin/items/{$item->id}/units/Pcs")
            ->assertStatus(422);
    }

    public function test_api_returns_units_filtered_by_context(): void
    {
        $gudang = $this->user('gudang');
        $item = $this->item();

        $item->itemUnits()->create([
            'unit_name' => 'Rim',
            'conversion_to_primary' => 500,
            'is_primary' => false,
            'context' => 'purchase',
        ]);

        $purchase = $this->actingAs($gudang)
            ->getJson("/api/items/{$item->id}/units?context=purchase")
            ->assertOk()
            ->json('units');

        $this->assertEqualsCanonicalizing(['Pcs', 'Pack', 'Rim'], array_column($purchase, 'name'));

        $usage = $this->actingAs($gudang)
            ->getJson("/api/items/{$item->id}/units?context=usage")
            ->assertOk()
            ->json('units');

        $this->assertEqualsCanonicalizing(['Pcs', 'Pack'], array_column($usage, 'name'));
    }

    public function test_request_in_large_unit_is_converted_and_received_in_primary_unit(): void
    {
        $gudang = $this->user('gudang');
        $hr = $this->user('hr');
        $item = $this->item(100);

        $this->actingAs($gudang)
            ->post('/gudang/request-barang', [
                'item_id' => $item->id,
                'quantity' => 2,
                'unit' => 'Pack',
                'priority' => 'Biasa',
                'reason' => 'Persediaan produksi',
            ])
            ->assertRedirect('/gudang/history');

        $request = StockRequest::firstOrFail();
        $this->assertSame(2, $request->quantity);
        $this->assertSame('Pack', $request->unit);
        $this->assertSame(400, $request->quantity_in_primary);
        $this->assertSame(100, $item->fresh()->stock, 'Permintaan tidak boleh mengubah stok.');

        $this->actingAs($hr)
            ->post("/hr/requests/{$request->id}/approve", ['note' => 'Disetujui'])
            ->assertRedirect();

        $this->assertSame(100, $item->fresh()->stock, 'Approval tidak boleh mengubah stok.');

        $this->actingAs($gudang)
            ->post('/gudang/penerimaan', [
                'stock_request_id' => $request->id,
                'received_quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertSame(500, $item->fresh()->stock);

        $movement = StockMovement::where('type', StockMovement::TYPE_IN)->latest('id')->firstOrFail();
        $this->assertSame(2, $movement->quantity);
        $this->assertSame('Pack', $movement->unit);
        $this->assertSame(400, $movement->quantity_in_primary);
    }

    public function test_stock_out_in_small_unit_only_deducts_converted_quantity(): void
    {
        $gudang = $this->user('gudang');
        $item = $this->item(450);

        $this->actingAs($gudang)
            ->post('/gudang/barang-keluar', [
                'item_id' => $item->id,
                'quantity' => 50,
                'unit' => 'Pcs',
                'reason' => 'Pemakaian produksi',
            ])
            ->assertRedirect();

        $this->assertSame(400, $item->fresh()->stock);

        $movement = StockMovement::where('type', StockMovement::TYPE_OUT)->latest('id')->firstOrFail();
        $this->assertSame(50, $movement->quantity);
        $this->assertSame('Pcs', $movement->unit);
        $this->assertSame(50, $movement->quantity_in_primary);
        $this->assertSame(400, $movement->balance_after);

        // 1 Pack = 200 Pcs
        $this->actingAs($gudang)
            ->post('/gudang/barang-keluar', [
                'item_id' => $item->id,
                'quantity' => 1,
                'unit' => 'Pack',
                'reason' => 'Pemakaian produksi',
            ])
            ->assertRedirect();

        $this->assertSame(200, $item->fresh()->stock);

        $movement = StockMovement::where('type', StockMovement::TYPE_OUT)->latest('id')->firstOrFail();
        $this->assertSame(1, $movement->quantity);
        $this->assertSame('Pack', $movement->unit);
        $this->assertSame(200, $movement->quantity_in_primary);
        $this->assertSame(200, $movement->balance_after);
    }

    public function test_stock_out_rejects_quantity_above_converted_stock(): void
    {
        $gudang = $this->user('gudang');
        $item = $this->item(150);

        $this->actingAs($gudang)
            ->post('/gudang/barang-keluar', [
                'item_id' => $item->id,
                'quantity' => 1,
                'unit' => 'Pack',
                'reason' => 'Pemakaian produksi',
            ])
            ->assertSessionHas('error');

        $this->assertSame(150, $item->fresh()->stock);
        $this->assertSame(0, StockMovement::where('type', StockMovement::TYPE_OUT)->count());
    }
}
