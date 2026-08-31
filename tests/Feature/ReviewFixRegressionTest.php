<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, string $suffix = ''): User
    {
        return User::create([
            'name' => ucfirst($role).' User'.$suffix,
            'email' => $role.$suffix.'-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
        ]);
    }

    protected function makeLocation(string $rack = 'A', int $number = 1): StorageLocation
    {
        $prefix = StorageLocation::getPrefixForRack($rack);

        return StorageLocation::create([
            'code' => $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT),
            'rack' => $rack,
            'number' => $number,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);
    }

    protected function makeItem(int $stock = 10): Item
    {
        $location = $this->makeLocation();

        return Item::create([
            'name' => 'Barang Regresi '.uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);
    }

    protected function makeRequest(User $user, Item $item, int $quantity = 2): StockRequest
    {
        return StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Butuh untuk operasional',
            'status' => 'Menunggu Review',
        ]);
    }

    public function test_approve_is_idempotent_and_does_not_double_stock(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem(10);
        $stockRequest = $this->makeRequest($gudang, $item, 2);

        $this->actingAs($hr)->post('/hr/requests/'.$stockRequest->id.'/approve')
            ->assertRedirect('/hr/approval');

        $stockRequest->refresh();
        $item->refresh();

        $this->assertSame('Disetujui', $stockRequest->status);
        $this->assertSame(10, $item->stock, 'Approval tidak boleh mengubah stok.');

        $this->actingAs($hr)->post('/hr/requests/'.$stockRequest->id.'/approve')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $item->refresh();
        $this->assertSame(10, $item->stock, 'Approve kedua tidak boleh mengubah stok.');
        $this->assertSame(1, $stockRequest->requestHistories()->where('status', 'Disetujui')->count());
    }

    public function test_approve_after_reject_does_not_modify_stock(): void
    {
        $hr = $this->makeUser('hr');
        $gudang = $this->makeUser('gudang');
        $item = $this->makeItem(10);
        $stockRequest = $this->makeRequest($gudang, $item, 2);

        $this->actingAs($hr)->post('/hr/requests/'.$stockRequest->id.'/reject', [
            'note' => 'Stok tidak tersedia',
        ])->assertRedirect('/hr/approval');

        $this->actingAs($hr)->post('/hr/requests/'.$stockRequest->id.'/approve')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $item->refresh();
        $this->assertSame(10, $item->stock);
        $this->assertSame('Ditolak', $stockRequest->refresh()->status);
    }

    public function test_created_user_without_password_gets_flag_and_no_plaintext_in_audit(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Staff Sementara',
            'email' => 'sementara-'.uniqid().'@example.com',
            'password' => '',
            'role' => 'gudang',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'like', 'sementara-%')->latest()->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->must_change_password);

        $createdAudit = AuditLog::where('action', 'created_user')->latest()->first();
        $this->assertNotNull($createdAudit);
        $this->assertStringNotContainsString('password', strtolower((string) $createdAudit->details));
        $this->assertStringNotContainsString('password', strtolower($createdAudit->action));
    }

    public function test_login_with_must_change_password_redirects_to_change_password(): void
    {
        $user = User::create([
            'name' => 'Wajib Ganti',
            'email' => 'wajibganti-'.uniqid().'@example.com',
            'password' => Hash::make('temp123456'),
            'role' => 'gudang',
            'must_change_password' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'temp123456',
        ])->assertRedirect('/ubah-password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_changing_password_clears_flag_and_redirects_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Wajib Ganti 2',
            'email' => 'wajibganti2-'.uniqid().'@example.com',
            'password' => Hash::make('temp123456'),
            'role' => 'gudang',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->post('/ubah-password', [
            'current_password' => 'temp123456',
            'password' => 'barubaru123',
            'password_confirmation' => 'barubaru123',
        ])->assertRedirect('/gudang/dashboard');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('barubaru123', $user->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::create([
            'name' => 'Wajib Ganti 3',
            'email' => 'wajibganti3-'.uniqid().'@example.com',
            'password' => Hash::make('temp123456'),
            'role' => 'gudang',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->post('/ubah-password', [
            'current_password' => 'salah',
            'password' => 'barubaru123',
            'password_confirmation' => 'barubaru123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_login_as_records_audit_and_impersonation_session(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');

        $this->actingAs($admin)->post('/admin/users/'.$gudang->id.'/login-as')
            ->assertRedirect('/gudang/dashboard');

        $this->assertSame($gudang->id, auth()->id());
        $this->assertSame($admin->id, session('impersonate_by'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'impersonated_user',
            'target_id' => $gudang->id,
        ]);
    }

    public function test_login_as_gudang_then_stop_then_login_as_hr(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');

        $this->actingAs($admin)->post('/admin/users/'.$gudang->id.'/login-as')
            ->assertRedirect('/gudang/dashboard');

        $this->assertSame($gudang->id, auth()->id());
        $this->assertSame($admin->id, session('impersonate_by'));

        $this->post('/admin/impersonation/stop')
            ->assertRedirect('/admin/dashboard');

        $this->assertSame($admin->id, auth()->id());
        $this->assertNull(session('impersonate_by'));

        $this->actingAs($admin->refresh())->post('/admin/users/'.$hr->id.'/login-as')
            ->assertRedirect('/hr/dashboard');

        $this->assertSame($hr->id, auth()->id());
        $this->assertSame($admin->id, session('impersonate_by'));
    }

    public function test_stop_impersonation_clears_session_completely(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');

        $this->actingAs($admin)->post('/admin/users/'.$gudang->id.'/login-as');

        $this->assertSame($gudang->id, auth()->id());
        $this->assertNotNull(session('impersonate_by'));

        $this->post('/admin/impersonation/stop');

        $this->assertSame($admin->id, auth()->id());
        $this->assertNull(session('impersonate_by'));

        $this->get('/admin/users')
            ->assertOk();

        $this->get('/gudang/dashboard')
            ->assertForbidden();
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy');
    }

    public function test_dev_mode_does_not_bypass_role_in_production(): void
    {
        app()['env'] = 'production';

        $gudang = $this->makeUser('gudang');

        $this->actingAs($gudang)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_restore_rejects_corrupt_archive(): void
    {
        $admin = $this->makeUser('admin');

        $dir = storage_path('app/backups');
        $this->assertTrue(is_dir($dir) || mkdir($dir, 0755, true));

        $corrupt = $dir.'/backup_corplogistics_rusak.zip';
        file_put_contents($corrupt, 'bukan zip');

        $this->actingAs($admin)->post('/admin/backups/'.basename($corrupt).'/restore')
            ->assertRedirect(route('admin.backups.index'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        @unlink($corrupt);
    }
}
