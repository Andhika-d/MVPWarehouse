<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_actions_create_audit_logs(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $item = Item::create([
            'name' => 'Lampu LED 15W',
            'unit' => 'pcs',
            'stock' => 10,
            'rack_location' => 'B',
        ]);

        $this->actingAs($gudang);

        $response = $this->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => 5,
            'priority' => 'Biasa',
            'reason' => 'Persediaan menipis',
        ]);

        $response->assertRedirect('/gudang/history');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $gudang->id,
            'action' => 'created_request',
            'target_type' => StockRequest::class,
        ]);
    }

    public function test_hr_approve_and_reject_actions_create_audit_logs(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $hr = User::factory()->create(['role' => 'hr']);
        $item = Item::create([
            'name' => 'Kertas A4',
            'unit' => 'rim',
            'stock' => 15,
            'rack_location' => 'A',
        ]);

        $stockRequest1 = StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'rim',
            'priority' => 'Biasa',
            'status' => 'Menunggu Review',
        ]);

        $stockRequest2 = StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 3,
            'unit' => 'rim',
            'priority' => 'Mendesak',
            'status' => 'Menunggu Review',
        ]);

        $this->actingAs($hr);

        $this->post("/hr/requests/{$stockRequest1->id}/approve", [
            'note' => 'Disetujui untuk kebutuhan operasional',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $hr->id,
            'action' => 'approved_request',
            'target_type' => StockRequest::class,
            'target_id' => $stockRequest1->id,
        ]);

        $this->post("/hr/requests/{$stockRequest2->id}/reject", [
            'note' => 'Stok di supplier sedang kosong',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $hr->id,
            'action' => 'rejected_request',
            'target_type' => StockRequest::class,
            'target_id' => $stockRequest2->id,
        ]);
    }

    public function test_user_login_and_logout_create_audit_logs(): void
    {
        $user = User::factory()->create([
            'email' => 'gudang@example.com',
            'password' => 'password',
            'role' => 'gudang',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'gudang@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/gudang/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'user_login',
        ]);

        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'user_logout',
        ]);
    }
}
