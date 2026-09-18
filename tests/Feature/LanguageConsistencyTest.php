<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\MonitoringIssue;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use App\Services\Monitoring\MonitoringIssueSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LanguageConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_configuration_is_indonesian(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', config('app.fallback_locale'));
        $this->assertSame('id_ID', config('app.faker_locale'));
        $this->assertSame('id', Carbon::getLocale());
    }

    public function test_login_page_uses_indonesian_branding(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sistem Manajemen Gudang Internal')
            ->assertSee('Monitoring Stok')
            ->assertSee('Riwayat Gudang')
            ->assertSee('Plan Dua')
            ->assertSee('Masuk')
            ->assertDontSee('Internal Warehouse Management System')
            ->assertDontSee('Stock Monitoring')
            ->assertDontSee('Warehouse History');
    }

    public function test_director_issues_page_translates_issue_terms(): void
    {
        $director = User::factory()->create(['role' => 'director']);
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
            'quantity' => 80,
            'unit' => 'pcs',
            'priority' => 'Biasa',
            'status' => 'Menunggu Review',
        ]);

        $createdAt = now()->subHours(80);
        $stockRequest->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        (new MonitoringIssueSynchronizer())->sync();

        $this->assertSame(1, MonitoringIssue::count());

        $this->actingAs($director)
            ->get('/director/issues')
            ->assertOk()
            ->assertSee('Masalah Aktif')
            ->assertSee('Distribusi Masalah Aktif per Tahap')
            ->assertSee('Request Menunggu Review')
            ->assertSee('Riwayat Selesai')
            ->assertDontSee('Issue Aktif')
            ->assertDontSee('Distribusi Issue');
    }

    public function test_gudang_stock_page_uses_cetak_instead_of_hard_copy(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
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
            ->assertSee('Monitoring Stok Barang')
            ->assertSee('Cetak')
            ->assertDontSee('Hard Copy');
    }

    public function test_audit_log_labels_are_indonesian_on_admin_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'gudang']);

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
            ->assertSee('Menambah barang')
            ->assertSee('Barang')
            ->assertSee('Pengguna')
            ->assertDontSee('Login As');

        $this->assertSame('Menambah barang', (new AuditLog(['action' => 'created_item']))->labelForAction());
        $this->assertSame('Logout pengguna', (new AuditLog(['action' => 'user_logout']))->labelForAction());
        $this->assertStringContainsString('Barang', (new AuditLog(['action' => 'created_item', 'target_type' => Item::class, 'target_id' => 1]))->labelForTarget());
        $this->assertSame('—', (new AuditLog(['action' => 'created_item', 'target_type' => null]))->labelForTarget());
    }

    public function test_user_management_page_uses_pengguna_and_peran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'gudang']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Peran')
            ->assertSee('Pengguna')
            ->assertSee('Login sebagai')
            ->assertSee('Reset Password')
            ->assertDontSee('Login As')
            ->assertDontSee('Reset PW')
            ->assertDontSee('Semua Role');
    }

    public function test_source_files_do_not_contain_legacy_english_labels(): void
    {
        $forbidden = [
            'Hard Copy',
            'Reset PW',
            'Login As',
            'Session import expired',
            'logged out',
            'Executive Monitoring',
            'Corporate Dashboard',
            'Internal Warehouse Management System',
        ];

        $directories = [resource_path('views'), app_path('Http/Controllers'), app_path('Exports'), app_path('Notifications'), app_path('Console/Commands')];

        $violations = [];

        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->getExtension() !== 'php' || $fileInfo->getFilename() === 'LanguageConsistencyTest.php') {
                    continue;
                }

                $file = $fileInfo->getPathname();
                $relative = str_replace('\\', '/', $file);
                $content = file_get_contents($file);

                foreach ($forbidden as $term) {
                    if (str_contains($content, $term)) {
                        $violations[] = $relative.' mengandung "'.$term.'"';
                    }
                }
            }
        }

        $this->assertSame([], $violations);
    }
}