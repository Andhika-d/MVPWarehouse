<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDelayedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Support\NotificationText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test',
            'email' => $role.'-'.uniqid().'@example.com',
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

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve', ['note' => 'Disetujui']);

        $this->assertTrue($gudang->notifications()->where('type', RequestApprovedNotification::class)->exists());
    }

    public function test_rejecting_request_notifies_requester_with_reason(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/reject', ['note' => 'Stok kosong']);

        $notification = $gudang->notifications()->where('type', RequestRejectedNotification::class)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Stok kosong', NotificationText::resolve($notification->data)['message']);
    }

    public function test_delaying_request_notifies_requester(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/delay', ['note' => 'Tunggu anggaran']);

        $this->assertTrue($gudang->notifications()->where('type', RequestDelayedNotification::class)->exists());
    }

    public function test_approve_all_notifies_requester_per_item(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $first = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 09:00:00']);
        $second = $this->makeRequest($gudang, $item, ['created_at' => '2026-08-01 10:00:00']);

        $this->actingAs($hr)->post(route('hr.requests.bulk-approve'), [
            'request_ids' => [$first->id, $second->id],
            'note' => 'OK',
        ]);

        $this->assertSame(2, $gudang->notifications()->where('type', RequestApprovedNotification::class)->count());
    }

    public function test_mark_all_read(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve');

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

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve');

        $notification = $gudang->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudang)
            ->post('/notifications/'.$notification->id.'/read')
            ->assertRedirect('/gudang/history/'.$request->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_read_returns_target_url_for_notification_dropdown(): void
    {
        $gudang = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $request = $this->makeRequest($gudang, $item);

        $this->actingAs($hr)->post('/hr/requests/'.$request->id.'/approve');
        $notification = $gudang->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudang)
            ->postJson('/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'url' => '/gudang/history/'.$request->id,
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

    public function test_shared_notification_and_flash_ui_render_in_english(): void
    {
        $gudang = $this->makeUser('gudang');
        $gudang->update(['locale' => 'en']);

        $this->actingAs($gudang)
            ->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Open notifications')
            ->assertSee('Notifications')
            ->assertSee('Mark all as read')
            ->assertSee('No notifications.')
            ->assertSee('just now')
            ->assertSee(':count minutes ago')
            ->assertSee(':count hours ago')
            ->assertSee(':count days ago')
            ->assertDontSee('Tandai semua dibaca')
            ->assertDontSee('Tidak ada notifikasi.');

        app()->setLocale('en');
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['email' => ['The email field is required.']]));
        $flashHtml = view('components.flash-messages', compact('errors'))->render();

        $this->assertStringContainsString('Action status', $flashHtml);
        $this->assertStringContainsString('Please check the information you entered.', $flashHtml);
    }

    public function test_notification_payloads_resolve_in_english(): void
    {
        app()->setLocale('en');

        $payloads = [
            [
                'data' => [
                    'title_key' => 'Permintaan Baru Masuk',
                    'message_key' => ':user mengajukan :quantity :unit :item (:priority).',
                    'params' => ['user' => 'Andi', 'quantity' => 2, 'unit' => 'pcs', 'item' => 'Kertas', 'priority' => 'Biasa'],
                ],
                'title' => 'New Request',
                'message' => 'Andi requested 2 pcs of Kertas (Normal).',
            ],
            [
                'data' => [
                    'title_key' => 'Permintaan Disetujui',
                    'message_key' => 'Permintaan :quantity :unit :item telah disetujui HR dan siap dibelanjakan.',
                    'params' => ['quantity' => 2, 'unit' => 'pcs', 'item' => 'Kertas'],
                ],
                'title' => 'Request Approved',
                'message' => 'HR approved the request for 2 pcs of Kertas. It is ready for purchase.',
            ],
            [
                'data' => [
                    'title_key' => 'Permintaan Ditolak',
                    'message_key' => 'Permintaan :quantity :unit :item ditolak HR. Alasan: :note',
                    'params' => ['quantity' => 2, 'unit' => 'pcs', 'item' => 'Kertas', 'note' => 'stok habis'],
                ],
                'title' => 'Request Rejected',
                'message' => 'HR rejected the request for 2 pcs of Kertas. Reason: stok habis',
            ],
            [
                'data' => [
                    'title_key' => 'Permintaan Ditunda',
                    'message_key' => 'Permintaan :quantity :unit :item ditunda oleh HR (Pending). Catatan: :note',
                    'params' => ['quantity' => 2, 'unit' => 'pcs', 'item' => 'Kertas', 'note' => 'tunggu anggaran'],
                ],
                'title' => 'Request Delayed',
                'message' => 'HR delayed the request for 2 pcs of Kertas (Pending). Note: tunggu anggaran',
            ],
            [
                'data' => [
                    'title_key' => 'Belanja Selesai',
                    'message_key' => 'Barang :quantity :unit :item telah diterima Gudang.',
                    'params' => ['quantity' => 2, 'unit' => 'pcs', 'item' => 'Kertas'],
                ],
                'title' => 'Purchase Completed',
                'message' => 'The warehouse received 2 pcs of Kertas.',
            ],
            [
                'data' => [
                    'title_key' => 'Ditutup Sebagian',
                    'message_key' => 'Sisa :remaining :unit dari :item ditutup. Status: :status.',
                    'params' => ['remaining' => 1, 'unit' => 'pcs', 'item' => 'Kertas', 'status' => 'Ditutup Sebagian'],
                ],
                'title' => 'Partially Closed',
                'message' => 'Remaining 1 pcs of Kertas closed. Status: Partially Closed.',
            ],
        ];

        foreach ($payloads as $payload) {
            $resolved = NotificationText::resolve($payload['data']);
            $this->assertSame($payload['title'], $resolved['title']);
            $this->assertSame($payload['message'], $resolved['message']);
        }
    }

    public function test_legacy_notification_payloads_are_translated_at_render_time(): void
    {
        $legacyPayloads = [
            [
                'type' => 'new_request',
                'title' => 'Permintaan Baru Masuk',
                'message' => 'Andi mengajukan 2 pcs Kertas (Biasa).',
                'expected_title' => 'New Request',
                'expected_message' => 'Andi requested 2 pcs Kertas (Normal).',
            ],
            [
                'type' => 'approved',
                'title' => 'Permintaan Disetujui',
                'message' => 'Permintaan 2 pcs Kertas telah disetujui HR dan siap dibelanjakan.',
                'expected_title' => 'Request Approved',
                'expected_message' => 'Request 2 pcs Kertas was approved by HR and is ready for purchase.',
            ],
            [
                'type' => 'rejected',
                'title' => 'Permintaan Ditolak',
                'message' => 'Permintaan 2 pcs Kertas ditolak HR. Alasan: Permintaan barang, stok habis',
                'expected_title' => 'Request Rejected',
                'expected_message' => 'Request 2 pcs Kertas was rejected by HR. Reason: Permintaan barang, stok habis',
            ],
            [
                'type' => 'delayed',
                'title' => 'Permintaan Ditunda',
                'message' => 'Permintaan 2 pcs Kertas ditunda oleh HR (Pending). Catatan: tunggu anggaran',
                'expected_title' => 'Request Delayed',
                'expected_message' => 'Request 2 pcs Kertas was delayed by HR (Pending). Note: tunggu anggaran',
            ],
            [
                'type' => 'completed',
                'title' => 'Belanja Selesai',
                'message' => 'Barang 2 pcs Kertas telah diterima Gudang.',
                'expected_title' => 'Purchase Completed',
                'expected_message' => 'Item 2 pcs Kertas was received by the warehouse.',
            ],
            [
                'type' => 'closed',
                'title' => 'Ditutup Sebagian',
                'message' => 'Sisa 1 pcs dari Kertas ditutup. Status:Ditutup Sebagian. Alasan: Dibatalkan otomatis, tidak dibutuhkan',
                'expected_title' => 'Partially Closed',
                'expected_message' => 'Remaining 1 pcs of Kertas closed. Status: Partially Closed. Reason: Dibatalkan otomatis, tidak dibutuhkan',
            ],
        ];

        app()->setLocale('en');
        foreach ($legacyPayloads as $legacy) {
            $resolved = NotificationText::resolve($legacy);
            $this->assertSame($legacy['expected_title'], $resolved['title']);
            $this->assertSame($legacy['expected_message'], $resolved['message']);
        }

        app()->setLocale('id');
        foreach ($legacyPayloads as $legacy) {
            $resolved = NotificationText::resolve($legacy);
            $this->assertSame($legacy['title'], $resolved['title']);
            $this->assertSame($legacy['message'], $resolved['message']);
        }
    }

    public function test_gudang_cannot_mark_others_notification_as_read(): void
    {
        $gudangA = $this->makeUser('gudang');
        $gudangB = $this->makeUser('gudang');
        $hr = $this->makeUser('hr');
        $item = $this->makeItem();
        $requestA = $this->makeRequest($gudangA, $item);
        $requestB = $this->makeRequest($gudangB, $item);

        $this->actingAs($hr)->post('/hr/requests/'.$requestA->id.'/approve');
        $this->actingAs($hr)->post('/hr/requests/'.$requestB->id.'/approve');

        $otherNotification = $gudangB->notifications()->where('type', RequestApprovedNotification::class)->first();

        $this->actingAs($gudangA)
            ->post('/notifications/'.$otherNotification->id.'/read')
            ->assertRedirect();

        $this->assertNull($otherNotification->fresh()->read_at);
    }
}
