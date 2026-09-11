<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDelayedNotification;
use App\Notifications\RequestRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function makeItem(): Item
    {
        return Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);
    }

    private function makeRequest(User $gudang, Item $item, array $overrides = []): StockRequest
    {
        $request = StockRequest::create(array_merge([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => $item->unit,
            'priority' => 'Biasa',
            'reason' => 'Butuh operasional',
            'status' => 'Menunggu Review',
        ], $overrides));

        if (isset($overrides['created_at'])) {
            $request->forceFill(['created_at' => $overrides['created_at']])->save();
        }

        return $request;
    }

    public function test_creating_request_notifies_all_active_hr_users(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr1 = $this->makeUser('hr');
        $hr2 = $this->makeUser('hr');
        $item = $this->makeItem();

        $this->actingAs($gudang)->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => 2,
            'priority' => 'Biasa',
            'reason' => 'Butuh operasional',
        ]);

        $this->assertTrue($hr1->notifications()->where('type', NewRequestNotification::class)->exists());
        $this->assertTrue($hr2->notifications()->where('type', NewRequestNotification::class)->exists());
        $this->assertFalse($gudang->notifications()->where('type', NewRequestNotification::class)->exists());
    }

    public function test_approving_request_notifies_requester(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve', ['note' => 'Disetujui']);

        $this->assertTrue($gudang->notifications()->where('type', RequestApprovedNotification::class)->exists());
    }

    public function test_rejecting_request_notifies_requester_with_reason(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/reject', ['note' => 'Stok kosong']);

        $notification = $gudang->notifications()->where('type', RequestRejectedNotification::class)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Stok kosong', $notification->data['message']);
    }

    public function test_delaying_request_notifies_requester(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/delay', ['note' => 'Tunggu anggaran']);

        $this->assertTrue($gudang->notifications()->where('type', RequestDelayedNotification::class)->exists());
    }

    public function test_approve_all_notifies_requester_per_item(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 09:00:00']);
        $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 10:00:00']);

        $this->actingAs($hr)->post('/hr/nota/2026-08-01/approve-all', ['note' => 'OK']);

        $this->assertSame(2, $gudang->notifications()->where('type', RequestApprovedNotification::class)->count());
    }

    public function test_mark_all_read(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve');

        $this->assertSame(1, $gudang->unreadNotifications()->count());

        $this->actingAs($gudang)->post('/notifications/read-all');

        $this->assertSame(0, $gudang->unreadNotifications()->count());
    }

    public function test_mark_read_redirects_to_target_url(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve');

        $notification = $gudang->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudang)
            ->post('/notifications/' . $notification->id . '/read')
            ->assertRedirect('/gudang/history/' . $request->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_read_returns_target_url_for_notification_dropdown(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $request->id . '/approve');
        $notification = $gudang->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudang)
            ->postJson('/notifications/' . $notification->id . '/read')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'url' => '/gudang/history/' . $request->id,
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_dropdown_renders_on_role_pages(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();

        $this->actingAs($gudang)
            ->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Notifikasi');

        $this->actingAs($hr)
            ->get('/hr/approval')
            ->assertOk()
            ->assertSee('Notifikasi');

        $this->assertTrue($item->exists);
    }

    public function test_gudang_cannot_mark_others_notification_as_read(): void
    {
        $gudangA = $this->makeUser('gudang');
        $gudangB = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $requestA = $this->makeRequest($gudangA, $item);
        $requestB = $this->makeRequest($gudangB, $item);

        $this->actingAs($hr)->post('/hr/requests/' . $requestA->id . '/approve');
        $this->actingAs($hr)->post('/hr/requests/' . $requestB->id . '/approve');

        $otherNotification = $gudangB->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudangA)
            ->post('/notifications/' . $otherNotification->id . '/read')
            ->assertRedirect();

        $this->assertNull($otherNotification->fresh()->read_at);
    }
}
