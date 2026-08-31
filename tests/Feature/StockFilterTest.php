<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
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
        $emptyLocation = $this->location('B-001', 'B', 1, StorageLocation::STATUS_EMPTY);
        $zeroStockLocation = $this->location('B-002', 'B', 2, StorageLocation::STATUS_OCCUPIED);

        Item::create([
            'name' => 'Lakban Besar',
            'storage_location_id' => $zeroStockLocation->id,
            'stock' => 0,
            'unit' => 'Pcs',
        ]);

        $itemEmptyResponse = $this->actingAs($user)->get('/gudang/stock?status=item_empty');
        $itemEmptyResponse->assertOk();
        $itemEmptyResponse->assertSee('Lakban Besar');
        $itemEmptyResponse->assertSee('B-002');
        $itemEmptyResponse->assertDontSee('B-001');

        $emptyResponse = $this->actingAs($user)->get('/gudang/stock?status=empty');
        $emptyResponse->assertOk();
        $emptyResponse->assertSee('B-001');
        $emptyResponse->assertDontSee('Lakban Besar');
    }
}
