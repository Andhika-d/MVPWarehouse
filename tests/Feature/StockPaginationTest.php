<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockPaginationTest extends TestCase
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

    public function test_gudang_stock_pagination_shows_20_per_page(): void
    {
        $gudang = $this->makeUser('gudang');

        for ($i = 1; $i <= 25; $i++) {
            $loc = $this->makeLocation('A', $i);
            Item::create([
                'name' => "Barang {$i}",
                'storage_location_id' => $loc->id,
                'stock' => $i,
                'unit' => 'Pcs',
            ]);
        }

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A');
        $response->assertOk();
        $response->assertSee('Barang 1');
        $response->assertSee('Barang 20');
        $response->assertDontSee('Barang 21');

        $response2 = $this->actingAs($gudang)->get('/gudang/stock?rack=A&page=2');
        $response2->assertOk();
        $response2->assertSee('Barang 21');
        $response2->assertDontSee('Barang 1');
    }

    public function test_hr_stock_pagination_shows_20_per_page(): void
    {
        $hr = $this->makeUser('hr');

        for ($i = 1; $i <= 25; $i++) {
            $loc = $this->makeLocation('C', $i);
            Item::create([
                'name' => "Item {$i}",
                'storage_location_id' => $loc->id,
                'stock' => $i,
                'unit' => 'Pcs',
            ]);
        }

        $response = $this->actingAs($hr)->get('/hr/stock?rack=C');
        $response->assertOk();
        $response->assertSee('Item 1');
        $response->assertSee('Item 20');
        $response->assertDontSee('Item 21');

        $response2 = $this->actingAs($hr)->get('/hr/stock?rack=C&page=2');
        $response2->assertOk();
        $response2->assertSee('Item 21');
        $response2->assertDontSee('Item 1');
    }

    private function makeOccupiedLocation(string $rack, int $number, string $itemName, int $stock = 10, string $unit = 'Pcs', ?string $size = null): Item
    {
        $loc = $this->makeLocation($rack, $number);
        $loc->update(['status' => StorageLocation::STATUS_OCCUPIED]);

        return Item::create([
            'name' => $itemName,
            'size' => $size,
            'storage_location_id' => $loc->id,
            'stock' => $stock,
            'unit' => $unit,
        ]);
    }

    public function test_gudang_stock_search_by_name(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('A', 1, 'Kertas HVS', 10, 'Rim');
        $this->makeOccupiedLocation('A', 2, 'Tinta Printer', 5, 'Botol');

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&search=Kertas');
        $response->assertOk();
        $response->assertSee('Kertas HVS');
        $response->assertDontSee('Tinta Printer');
    }

    public function test_gudang_stock_search_by_code(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('B', 5, 'Spidol', 3, 'Box');

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=B&search=RB-005');
        $response->assertOk();
        $response->assertSee('Spidol');
    }

    public function test_gudang_stock_search_by_sub_location(): void
    {
        $gudang = $this->makeUser('gudang');

        $loc = $this->makeLocation('A', 10);
        $loc->update(['sub_location' => 'A.1.1.1', 'status' => StorageLocation::STATUS_OCCUPIED]);
        Item::create(['name' => 'Marker', 'storage_location_id' => $loc->id, 'stock' => 2, 'unit' => 'Pcs']);

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&search=A.1.1.1');
        $response->assertOk();
        $response->assertSee('Marker');
    }

    public function test_gudang_stock_search_by_size(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('A', 1, 'Pipa', 10, 'Batang', '2 inch');
        $this->makeOccupiedLocation('A', 2, 'Pipa', 5, 'Batang', '4 inch');

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&search=4 inch');
        $response->assertOk();
        $response->assertSee('4 inch');
        $response->assertDontSee('2 inch');
    }

    public function test_gudang_stock_status_filter_occupied(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('A', 1, 'Pensil', 10, 'Buah');
        $this->makeLocation('A', 2);

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&status=occupied');
        $response->assertOk();
        $response->assertSee('Pensil');
    }

    public function test_gudang_stock_status_filter_empty(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('A', 1, 'Pensil', 10, 'Buah');
        $this->makeLocation('A', 2);

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&status=empty');
        $response->assertOk();
        $response->assertDontSee('Pensil');
        $response->assertSee('RA-002');
    }

    public function test_hr_stock_search_by_name(): void
    {
        $hr = $this->makeUser('hr');

        $this->makeOccupiedLocation('B', 1, 'Kertas HVS', 10, 'Rim');
        $this->makeOccupiedLocation('B', 2, 'Tinta Printer', 5, 'Botol');

        $response = $this->actingAs($hr)->get('/hr/stock?rack=B&search=Tinta');
        $response->assertOk();
        $response->assertSee('Tinta Printer');
        $response->assertDontSee('Kertas HVS');
    }

    public function test_stock_without_rack_selected_shows_rack_cards(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->makeOccupiedLocation('A', 1, 'Buku', 5, 'Pcs');

        $response = $this->actingAs($gudang)->get('/gudang/stock');
        $response->assertOk();
        $response->assertSee('Lihat Barang');
        $response->assertDontSee('Buku');
    }

    public function test_stock_page_search_preserves_across_pagination(): void
    {
        $gudang = $this->makeUser('gudang');

        for ($i = 1; $i <= 25; $i++) {
            $this->makeOccupiedLocation('A', $i, "Barang A {$i}", $i, 'Pcs');
        }

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A&search=Barang');
        $response->assertOk();
        $response->assertSee('Barang A 1');
    }

    public function test_stock_empty_rack_shows_message(): void
    {
        $gudang = $this->makeUser('gudang');

        $response = $this->actingAs($gudang)->get('/gudang/stock?rack=A');
        $response->assertOk();
        $response->assertSee('Tidak ada data ditemukan');
    }
}
