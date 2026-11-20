<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockFilterTest extends TestCase
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

    private function location(string $code, string $rack, int $number, string $status): StorageLocation
    {
        return StorageLocation::create([
            'code' => $code,
            'rack' => $rack,
            'number' => $number,
            'sub_location' => $code.'.1',
            'status' => $status,
        ]);
    }

    public function test_barang_kosong_filter_is_different_from_empty_location_filter(): void
    {
        $user = $this->user('gudang');
        $prefix = StorageLocation::getPrefixForRack('B');
        $emptyLocation = $this->location($prefix.'-001', 'B', 1, StorageLocation::STATUS_EMPTY);
        $zeroStockLocation = $this->location($prefix.'-002', 'B', 2, StorageLocation::STATUS_OCCUPIED);

        Item::create([
            'name' => 'Lakban Besar',
            'storage_location_id' => $zeroStockLocation->id,
            'stock' => 0,
            'unit' => 'Pcs',
        ]);

        $itemEmptyResponse = $this->actingAs($user)->get('/gudang/stock?status=item_empty');
        $itemEmptyResponse->assertOk();
        $itemEmptyResponse->assertSee('Terisi (Barang Kosong)');
        $this->assertSame([
            $zeroStockLocation->id,
        ], DB::table('storage_locations')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('items')
                    ->whereColumn('items.storage_location_id', 'storage_locations.id')
                    ->where('items.stock', 0);
            })
            ->pluck('id')
            ->all());

        $emptyResponse = $this->actingAs($user)->get('/gudang/stock?status=empty');
        $emptyResponse->assertOk();
        $emptyResponse->assertDontSee('Terisi (Barang Kosong)');
        $this->assertSame([
            $emptyLocation->id,
        ], StorageLocation::where('status', StorageLocation::STATUS_EMPTY)
            ->pluck('id')
            ->all());

        foreach (['hr', 'director'] as $role) {
            $this->actingAs($this->user($role))
                ->get("/{$role}/stock?status=item_empty")
                ->assertOk()
                ->assertSee('Terisi (Barang Kosong)');
        }
    }

    public function test_low_stock_filter_is_available_on_all_stock_pages(): void
    {
        $lowLocation = $this->location('RA-101', 'A', 101, StorageLocation::STATUS_OCCUPIED);
        $normalLocation = $this->location('RA-102', 'A', 102, StorageLocation::STATUS_OCCUPIED);
        $zeroLocation = $this->location('RA-103', 'A', 103, StorageLocation::STATUS_OCCUPIED);

        Item::create([
            'name' => 'Tali Menipis',
            'storage_location_id' => $lowLocation->id,
            'stock' => 3,
            'unit' => 'Pcs',
        ]);
        Item::create([
            'name' => 'Kabel Normal',
            'storage_location_id' => $normalLocation->id,
            'stock' => 20,
            'unit' => 'Pcs',
        ]);
        Item::create([
            'name' => 'Lakban Habis',
            'storage_location_id' => $zeroLocation->id,
            'stock' => 0,
            'unit' => 'Pcs',
        ]);

        $pages = [
            ['gudang', '/gudang/stock?low_stock=1'],
            ['hr', '/hr/stock?low_stock=1'],
            ['director', '/director/stock?low_stock=1'],
            ['admin', '/admin/items?low_stock=1'],
        ];

        foreach ($pages as [$role, $url]) {
            $this->actingAs($this->user($role))
                ->get($url)
                ->assertOk()
                ->assertSee('name="low_stock"', false)
                ->assertSee('Stok Menipis (1-5)')
                ->assertSee('Tali Menipis')
                ->assertDontSee('Kabel Normal')
                ->assertDontSee('Lakban Habis');
        }

        $this->actingAs($this->user('gudang'))
            ->get('/gudang/stock/print?low_stock=1')
            ->assertOk()
            ->assertSee('Tali Menipis')
            ->assertDontSee('Kabel Normal')
            ->assertDontSee('Lakban Habis');
    }
}
