<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeatureLockTest extends TestCase
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

    public function test_gudang_stock_page_shows_monitoring_table(): void
    {
        $loc = $this->makeLocation('B', 1);
        Item::create([
            'name' => 'Kertas HVS A4',
            'storage_location_id' => $loc->id,
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        $this->actingAs($this->makeUser('gudang'))
            ->get('/gudang/stock')
            ->assertOk()
            ->assertSee('Total Lokasi')
            ->assertSee('Rak')
            ->assertSee('Nama Barang')
            ->assertSee('Kertas HVS A4');

        $this->actingAs($this->makeUser('gudang'))
            ->get('/gudang/stock?rack=B')
            ->assertOk()
            ->assertSee('Kertas HVS A4');
    }

    public function test_hr_dashboard_has_no_locked_feature_panel(): void
    {
        $this->actingAs($this->makeUser('hr'))
            ->get('/hr/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Pantauan Permintaan')
            ->assertDontSee('v1.1');
    }

    public function test_admin_data_master_hides_warning_stock_badge(): void
    {
        $admin = $this->makeUser('admin');
        $loc = $this->makeLocation('B', 1);
        Item::create([
            'name' => 'Kertas HVS A4',
            'storage_location_id' => $loc->id,
            'stock' => 2,
            'unit' => 'Rim',
        ]);

        $response = $this->actingAs($admin)->get('/admin/items');
        $response->assertOk();
        $response->assertSee('Kertas HVS A4');
    }
}
