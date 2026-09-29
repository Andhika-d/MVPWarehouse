<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_language_via_session(): void
    {
        $this->post('/language', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', session('locale'));
    }

    public function test_authenticated_user_switch_persists_locale_in_database(): void
    {
        $user = User::factory()->create(['role' => 'gudang', 'locale' => 'id']);

        $this->actingAs($user)
            ->post('/language', ['locale' => 'en'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => 'en']);
        $this->assertSame('en', session('locale'));
    }

    public function test_admin_locale_dropdown_on_users_page_renders_in_english(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'locale' => 'en']);
        User::factory()->create(['role' => 'gudang', 'locale' => 'id']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manage Users')
            ->assertSee('Language')
            ->assertSee('Log in as')
            ->assertSee('value="en" selected', false);
    }

    public function test_admin_can_change_another_users_locale(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gudang = User::factory()->create(['role' => 'gudang', 'locale' => 'id']);

        $this->actingAs($admin)
            ->post(route('admin.users.locale', $gudang), ['locale' => 'en']);

        $this->assertDatabaseHas('users', ['id' => $gudang->id, 'locale' => 'en']);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'gudang', 'locale' => 'id']);

        $this->actingAs($user)
            ->post('/language', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('id', $user->fresh()->locale);
    }
}
