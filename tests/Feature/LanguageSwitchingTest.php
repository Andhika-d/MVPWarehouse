<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StorageLocation;
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

    public function test_gudang_workspace_pages_render_in_english(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang', 'locale' => 'en']);

        $this->actingAs($gudang)
            ->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Create Request')
            ->assertSee('Request Flow')
            ->assertSee('Item Name')
            ->assertSee('Type to search for an item')
            ->assertSee('Attachment');

        $this->actingAs($gudang)
            ->get('/gudang/history')
            ->assertOk()
            ->assertSee('Request History')
            ->assertSee('Search item name')
            ->assertSee('All Statuses')
            ->assertSee('Preview Export');

        $this->actingAs($gudang)
            ->get('/gudang/barang-keluar')
            ->assertOk()
            ->assertSee('Log Outgoing Goods')
            ->assertSee('Select purpose')
            ->assertSee('Item Name');

        $this->actingAs($gudang)
            ->get('/gudang/penerimaan')
            ->assertOk()
            ->assertSee('Incoming Goods')
            ->assertSee('Requests Awaiting Receipt')
            ->assertSee('No requests awaiting receipt.');
    }

    public function test_gudang_stock_page_renders_in_english(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang', 'locale' => 'en']);
        $location = StorageLocation::create([
            'rack' => 'A',
            'number' => 1,
            'code' => 'A-01',
            'status' => StorageLocation::STATUS_OCCUPIED,
        ]);
        Item::create([
            'name' => 'Lampu LED',
            'storage_location_id' => $location->id,
            'unit' => 'pcs',
            'stock' => 10,
        ]);

        $this->actingAs($gudang)
            ->get('/gudang/stock?rack=A')
            ->assertOk()
            ->assertSee('Stock Monitoring')
            ->assertSee('Total Locations')
            ->assertSee('Total Stock')
            ->assertSee('Code Tag')
            ->assertSee('Print');
    }

    public function test_hr_and_director_pages_render_in_english(): void
    {
        $hr = User::factory()->create(['role' => 'hr', 'locale' => 'en']);
        $director = User::factory()->create(['role' => 'director', 'locale' => 'en']);
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = Item::create([
            'name' => 'Sarung Tangan',
            'unit' => 'pcs',
            'stock' => 5,
            'rack_location' => 'A',
        ]);
        $stockRequest = StockRequest::create([
            'user_id' => $requester->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit' => 'pcs',
            'priority' => 'Biasa',
            'status' => 'Menunggu Review',
        ]);

        $this->actingAs($hr)
            ->get('/hr/approval')
            ->assertOk()
            ->assertSee('Request Verification Desk')
            ->assertSee('Approve All Displayed')
            ->assertSee('Reject All')
            ->assertSee('Search item name');

        $this->actingAs($hr)
            ->get('/hr/requests/'.$stockRequest->id)
            ->assertOk()
            ->assertSee('Request Detail for Review')
            ->assertSee('Review Decision')
            ->assertSee('Receive')
            ->assertSee('Status Timeline');

        $this->actingAs($director)
            ->get('/director/requests')
            ->assertOk()
            ->assertSee('All Item Requests')
            ->assertSee('All Statuses')
            ->assertSee('All Priorities');

        $this->actingAs($director)
            ->get(route('director.request-detail', $stockRequest))
            ->assertOk()
            ->assertSee('Duration Analysis')
            ->assertSee('Timeline');

        $this->actingAs($director)
            ->get('/director/issues')
            ->assertOk()
            ->assertSee('Active Issues')
            ->assertSee('All Severity Levels')
            ->assertSee('No issues recorded yet.');
    }

    public function test_admin_audit_page_renders_in_english(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'locale' => 'en']);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'created_item',
            'target_type' => Item::class,
            'target_id' => 1,
            'details' => 'Menambahkan barang Test di A-01',
        ]);

        $this->actingAs($admin)
            ->get('/admin/audit')
            ->assertOk()
            ->assertSee('Global Audit Log')
            ->assertSee('All Actions')
            ->assertSee('Time')
            ->assertSee('Added item')
            ->assertSee('Detail');
    }

    public function test_indonesian_default_users_still_see_indonesian_pages(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang', 'locale' => 'id']);

        $this->actingAs($gudang)
            ->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Buat Permintaan')
            ->assertSee('Alur Pengajuan')
            ->assertDontSee('Create Request')
            ->assertDontSee('Request Flow');
    }
}
