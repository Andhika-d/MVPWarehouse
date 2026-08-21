<?php

namespace Tests\Feature;

use App\Models\Item;
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

    public function test_gudang_stock_page_shows_rack_cards(): void
    {
        Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        $this->actingAs($this->makeUser('gudang'))
            ->get('/gudang/stock')
            ->assertOk()
            ->assertSee('Lihat Barang')
            ->assertSee('Rak B')
            ->assertDontSee('Kertas HVS A4');

        $this->actingAs($this->makeUser('gudang'))
            ->get('/gudang/stock?rack=B')
            ->assertOk()
            ->assertSee('Kertas HVS A4');
    }

    public function test_hr_dashboard_shows_statistik_locked_card(): void
    {
        $this->actingAs($this->makeUser('hr'))
            ->get('/hr/dashboard')
            ->assertOk()
            ->assertSee('v1.1')
            ->assertSee('Dashboard Statistik');
    }

    public function test_admin_data_master_hides_warning_stock_badge(): void
    {
        Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 2,
            'unit' => 'Rim',
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kertas HVS A4')
            ->assertDontSee('Menipis');
    }
}
