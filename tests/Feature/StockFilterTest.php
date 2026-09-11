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
}
