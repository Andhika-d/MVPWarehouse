<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_can_submit_request_and_view_history(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Utama',
            'email' => 'gudang@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $item = Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        $response = $this->actingAs($gudang)->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => 2,
            'priority' => 'Biasa',
            'reason' => 'Butuh untuk operasional',
        ]);

        $response->assertRedirect('/gudang/history');
        $this->assertDatabaseHas('stock_requests', [
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'status' => 'Menunggu Review',
        ]);

        $historyResponse = $this->actingAs($gudang)->get('/gudang/history');
        $historyResponse->assertSee('Kertas HVS A4');
    }

    public function test_gudang_dashboard_shows_real_request_activity(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Utama',
            'email' => 'gudang-dashboard@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $item = Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Rim',
            'priority' => 'Biasa',
            'reason' => 'Butuh untuk operasional',
            'status' => 'Menunggu Review',
        ]);

        $response = $this->actingAs($gudang)->get('/gudang/dashboard');

        $response->assertSee($item->name);
        $response->assertSee('Menunggu Review');
    }

    public function test_admin_can_create_item_and_manage_master_data(): void
    {
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-master@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/items', [
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 10,
            'unit' => 'Rim',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertDatabaseHas('items', [
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
        ]);

        $dashboardResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboardResponse->assertSee('Kertas HVS A4');
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Tester',
            'email' => 'gudang-access@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $response = $this->actingAs($gudang)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_gudang_cannot_access_hr_routes(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Tester',
            'email' => 'gudang-hr-access@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $response = $this->actingAs($gudang)->get('/hr/approval');

        $response->assertForbidden();
    }

    public function test_hr_cannot_access_gudang_routes(): void
    {
        $hr = User::create([
            'name' => 'HR Tester',
            'email' => 'hr-gudang-access@example.com',
            'password' => Hash::make('password'),
            'role' => 'hr',
        ]);

        $response = $this->actingAs($hr)->get('/gudang/request-barang');

        $response->assertForbidden();
    }

    public function test_admin_can_create_user_and_record_audit_log(): void
    {
        $admin = User::create([
            'name' => 'Admin Audit',
            'email' => 'admin-audit@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'User Baru',
            'email' => 'user-baru@example.com',
            'password' => 'password123',
            'role' => 'gudang',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'user-baru@example.com',
            'role' => 'gudang',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created_user',
        ]);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Reset',
            'email' => 'admin-reset@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $user = User::create([
            'name' => 'Staff Gudang',
            'email' => 'staff-reset@example.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'gudang',
        ]);

        $response = $this->actingAs($admin)->post('/admin/users/' . $user->id . '/reset-password');

        $response->assertRedirect('/admin/dashboard');
        $user->refresh();
        $this->assertFalse(Hash::check('password123', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_admin_can_update_role_and_delete_user(): void
    {
        $admin = User::create([
            'name' => 'Admin User Mgmt',
            'email' => 'admin-user-mgmt@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $user = User::create([
            'name' => 'Staff Lama',
            'email' => 'staff-lama@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $this->actingAs($admin)->post('/admin/users/' . $user->id . '/role', [
            'role' => 'hr',
        ]);

        $user->refresh();
        $this->assertSame('hr', $user->role);

        $this->actingAs($admin)->delete('/admin/users/' . $user->id);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_can_update_and_delete_item(): void
    {
        $admin = User::create([
            'name' => 'Admin CRUD',
            'email' => 'admin-crud@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $item = Item::create([
            'name' => 'Stapler',
            'rack_location' => 'A',
            'stock' => 4,
            'unit' => 'Pcs',
        ]);

        $this->actingAs($admin)->put('/admin/items/' . $item->id, [
            'name' => 'Stapler Premium',
            'rack_location' => 'E',
            'stock' => 6,
            'unit' => 'Pcs',
        ]);

        $item->refresh();
        $this->assertSame('Stapler Premium', $item->name);
        $this->assertSame('E', $item->rack_location);

        $this->actingAs($admin)->delete('/admin/items/' . $item->id);

        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    public function test_hr_can_approve_request_and_history_is_updated(): void
    {
        $hr = User::create([
            'name' => 'HR',
            'email' => 'hr@example.com',
            'password' => Hash::make('password'),
            'role' => 'hr',
        ]);

        $item = Item::create([
            'name' => 'Mouse Wireless',
            'rack_location' => 'C',
            'stock' => 3,
            'unit' => 'Pcs',
        ]);

        $request = StockRequest::create([
            'user_id' => 1,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Pcs',
            'priority' => 'Mendesak',
            'reason' => 'Butuh cepat',
            'status' => 'Menunggu Review',
        ]);

        $response = $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve', [
            'note' => 'Disetujui sesuai kebutuhan',
        ]);

        $response->assertRedirect('/hr/approval');
        $request->refresh();
        $item->refresh();
        $this->assertSame('Disetujui', $request->status);
        $this->assertSame(1, $request->requestHistories()->count());
        $this->assertSame(3, $item->stock, 'Approval tidak boleh mengubah stok.');
    }
}
