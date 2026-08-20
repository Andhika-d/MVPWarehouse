<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevModeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test',
            'email' => $role.'-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_role_lock_still_applies_when_dev_mode_off(): void
    {
        $this->actingAs($this->makeUser('gudang'))
            ->get('/hr/approval')
            ->assertForbidden();
    }

    public function test_dev_mode_unlocks_other_role_pages(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/admin/settings/dev-mode/toggle')
            ->assertRedirect('/admin/dashboard');

        $this->assertTrue(Setting::enabled('dev_mode'));

        $this->actingAs($this->makeUser('gudang'))
            ->get('/hr/approval')
            ->assertOk();
    }

    public function test_only_admin_can_toggle_dev_mode(): void
    {
        $this->actingAs($this->makeUser('gudang'))
            ->post('/admin/settings/dev-mode/toggle')
            ->assertForbidden();

        $this->assertFalse(Setting::enabled('dev_mode'));
    }

    public function test_dev_mode_toggle_is_audited(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/admin/settings/dev-mode/toggle');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'dev_mode_enabled',
        ]);
    }

    public function test_admin_can_login_as_gudang_user(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');

        $this->actingAs($admin)
            ->post('/admin/users/'.$gudang->id.'/login-as')
            ->assertRedirect('/gudang/dashboard');

        $this->assertAuthenticatedAs($gudang);
        $this->assertSame($admin->id, session('impersonate_by'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'impersonated_user',
            'target_id' => $gudang->id,
        ]);
    }

    public function test_impersonated_user_can_stop_and_return_to_admin(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');

        $this->actingAs($admin)->post('/admin/users/'.$gudang->id.'/login-as');
        $this->assertAuthenticatedAs($gudang);

        $this->post('/admin/impersonation/stop')
            ->assertRedirect('/admin/dashboard')
            ->assertSessionMissing('impersonate_by');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_login_as_self(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post('/admin/users/'.$admin->id.'/login-as')
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonate_by'));
    }

    public function test_admin_cannot_login_as_another_admin(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post('/admin/users/'.$other->id.'/login-as')
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonate_by'));
    }

    public function test_admin_cannot_login_as_inactive_user(): void
    {
        $admin = $this->makeUser('admin');
        $inactive = $this->makeUser('gudang');
        $inactive->update(['is_active' => false]);

        $this->actingAs($admin)
            ->post('/admin/users/'.$inactive->id.'/login-as')
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonate_by'));
    }

    public function test_non_admin_cannot_login_as(): void
    {
        $hr = $this->makeUser('hr');

        $this->actingAs($this->makeUser('gudang'))
            ->post('/admin/users/'.$hr->id.'/login-as')
            ->assertForbidden();
    }
}
