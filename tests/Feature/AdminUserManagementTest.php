<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_page_offers_director_role_option(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('<option value="director">Direktur</option>', false)
            ->assertSee('value="director"', false)
            ->assertSee('Direktur', false);
    }

    public function test_admin_can_create_director_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Bapak Direktur',
            'email' => 'direktur@example.com',
            'password' => 'rahasia123',
            'role' => 'director',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'direktur@example.com',
            'role' => 'director',
            'must_change_password' => false,
        ]);
    }

    public function test_director_role_filter_on_users_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $director = User::factory()->create(['role' => 'director']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'director']));

        $response->assertOk()
            ->assertSee($director->name)
            ->assertSee('value="director" selected', false);
    }
}
