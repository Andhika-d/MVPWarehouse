<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Audit menyeluruh untuk semua endpoint cetak (print/PDF), export Excel,
 * dan import Excel. Tujuannya memastikan tidak ada endpoint yang
 * menghasilkan 404/500 ketika database masih kosong (belum ada data).
 */
class PrintExportAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2?: string}>
     */
    public static function endpointProvider(): array
    {
        return [
            // Gudang
            'gudang stock print' => ['/gudang/stock/print', 'gudang'],
            'gudang stock print with filters' => ['/gudang/stock/print?rack=all&status=all&search=', 'gudang'],
            'gudang movements index' => ['/gudang/movements', 'gudang'],
            'gudang movements export preview' => ['/gudang/movements/export/preview', 'gudang'],
            'gudang movements export preview empty type' => ['/gudang/movements/export/preview?export_type=', 'gudang'],
            'gudang movements export preview all' => ['/gudang/movements/export/preview?export_type=all&type=all&item_id=', 'gudang'],
            'gudang movements export excel' => ['/gudang/movements/export/excel', 'gudang'],
            'gudang movements export excel empty type' => ['/gudang/movements/export/excel?export_type=', 'gudang'],
            'gudang movements export excel all' => ['/gudang/movements/export/excel?export_type=all&type=all&item_id=', 'gudang'],
            'gudang history index' => ['/gudang/history', 'gudang'],
            'gudang history export preview' => ['/gudang/history/export/preview', 'gudang'],
            'gudang history export preview empty filters' => ['/gudang/history/export/preview?status=all&search=&period_mode=', 'gudang'],
            'gudang history export pdf' => ['/gudang/history/export/pdf', 'gudang'],
            'gudang history export pdf empty filters' => ['/gudang/history/export/pdf?status=all&search=&period_mode=', 'gudang'],
            'gudang history export excel' => ['/gudang/history/export/excel', 'gudang'],
            'gudang history export excel empty filters' => ['/gudang/history/export/excel?status=all&search=&period_mode=', 'gudang'],
            'gudang history print' => ['/gudang/history/print', 'gudang'],
            'gudang location change index' => ['/gudang/location-change', 'gudang'],
            'gudang location change preview' => ['/gudang/location-change/export/preview', 'gudang'],
            'gudang location change preview empty filters' => ['/gudang/location-change/export/preview?status=all&search=&period_mode=', 'gudang'],
            'gudang location change excel' => ['/gudang/location-change/export/excel', 'gudang'],
            'gudang location change excel empty filters' => ['/gudang/location-change/export/excel?status=all&search=&period_mode=', 'gudang'],
            'gudang location change pdf' => ['/gudang/location-change/export/pdf', 'gudang'],
            'gudang location change pdf empty filters' => ['/gudang/location-change/export/pdf?status=all&search=&period_mode=', 'gudang'],

            // HR
            'hr stock print' => ['/hr/stock/print', 'hr'],
            'hr movements' => ['/hr/movements', 'hr'],
            'hr approval index' => ['/hr/approval', 'hr'],
            'hr approval export preview' => ['/hr/approval/export/preview', 'hr'],
            'hr approval export preview empty filters' => ['/hr/approval/export/preview?status=all&search=&period_mode=', 'hr'],
            'hr approval export pdf' => ['/hr/approval/export/pdf', 'hr'],
            'hr approval export pdf empty filters' => ['/hr/approval/export/pdf?status=all&search=&period_mode=', 'hr'],
            'hr approval export excel' => ['/hr/approval/export/excel', 'hr'],
            'hr approval export excel empty filters' => ['/hr/approval/export/excel?status=all&search=&period_mode=', 'hr'],
            'procurement notes index' => ['/riwayat-pengadaan', 'hr'],
            'procurement notes print period' => ['/riwayat-pengadaan/cetak', 'hr'],
            'procurement notes print period empty filters' => ['/riwayat-pengadaan/cetak?status=all&search=&period_mode=', 'hr'],
            'procurement notes print period form filters' => ['/riwayat-pengadaan/cetak?request_status=all&priority=all&search=&period_mode=&orientation=portrait', 'hr'],
            'procurement notes excel period' => ['/riwayat-pengadaan/export/excel', 'hr'],
            'procurement notes excel period empty filters' => ['/riwayat-pengadaan/export/excel?status=all&search=&period_mode=', 'hr'],
            'procurement notes excel period form filters' => ['/riwayat-pengadaan/export/excel?request_status=all&priority=all&search=&period_mode=', 'hr'],

            // Director
            'director stock' => ['/director/stock', User::ROLE_DIRECTOR],
            'director stock print' => ['/director/stock/print', User::ROLE_DIRECTOR],
            'director stock print with filters' => ['/director/stock/print?rack=all&status=all&search=', User::ROLE_DIRECTOR],
            'director movements' => ['/director/movements', User::ROLE_DIRECTOR],

            // Admin
            'admin import form' => ['/admin/import', 'admin'],
            'admin audit index' => ['/admin/audit', 'admin'],
            'admin audit preview' => ['/admin/audit/export/preview', 'admin'],
            'admin audit preview empty filters' => ['/admin/audit/export/preview?action=all&search=&period_mode=', 'admin'],
            'admin audit excel' => ['/admin/audit/export/excel', 'admin'],
            'admin audit excel empty filters' => ['/admin/audit/export/excel?action=all&search=&period_mode=', 'admin'],
            'admin location changes index' => ['/admin/location-changes', 'admin'],
            'admin location changes preview' => ['/admin/location-changes/export/preview', 'admin'],
            'admin location changes preview empty filters' => ['/admin/location-changes/export/preview?status=all&search=&period_mode=', 'admin'],
            'admin location changes excel' => ['/admin/location-changes/export/excel', 'admin'],
            'admin location changes excel empty filters' => ['/admin/location-changes/export/excel?status=all&search=&period_mode=', 'admin'],
            'admin location changes pdf' => ['/admin/location-changes/export/pdf', 'admin'],
            'admin location changes pdf empty filters' => ['/admin/location-changes/export/pdf?status=all&search=&period_mode=', 'admin'],

            // Admin Coil
            'admin coil tags index' => ['/admin-coil/coil-tags', User::ROLE_ADMIN_COIL],
            'admin coil tags export excel' => ['/admin-coil/coil-tags/export', User::ROLE_ADMIN_COIL],
            'admin coil tags export excel empty filters' => ['/admin-coil/coil-tags/export?search=&location=all&period_mode=', User::ROLE_ADMIN_COIL],
            'admin coil tags print' => ['/admin-coil/coil-tags/print', User::ROLE_ADMIN_COIL],
            'admin coil tags print empty filters' => ['/admin-coil/coil-tags/print?search=&location=all&period_mode=', User::ROLE_ADMIN_COIL],
            'admin coil tags pdf' => ['/admin-coil/coil-tags/pdf', User::ROLE_ADMIN_COIL],
            'admin coil tags pdf empty filters' => ['/admin-coil/coil-tags/pdf?search=&location=all&period_mode=', User::ROLE_ADMIN_COIL],
            'admin coil movements index' => ['/admin-coil/pergerakan-coil', User::ROLE_ADMIN_COIL],
            'admin coil movements export' => ['/admin-coil/pergerakan-coil/export', User::ROLE_ADMIN_COIL],
            'admin coil movements export empty filters' => ['/admin-coil/pergerakan-coil/export?search=&location=all&type=all&period_mode=', User::ROLE_ADMIN_COIL],
            'admin coil location import form' => ['/admin-coil/lokasi/import', User::ROLE_ADMIN_COIL],
            'admin coil location import template' => ['/admin-coil/lokasi/import/template', User::ROLE_ADMIN_COIL],
            'admin coil locations index' => ['/admin-coil/lokasi', User::ROLE_ADMIN_COIL],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function test_endpoint_is_reachable_with_empty_database(string $uri, string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $response = $this->actingAs($user)->get($uri);

        $status = $response->getStatusCode();

        $this->assertNotSame(404, $status, "Endpoint {$uri} mengembalikan 404 Not Found.");
        $this->assertNotSame(500, $status, "Endpoint {$uri} mengembalikan 500 Server Error.");
        $this->assertContains($status, [200, 302], "Endpoint {$uri} mengembalikan status tak terduga {$status}.");
    }

    #[DataProvider('endpointProvider')]
    public function test_preview_and_download_links_on_rendered_page_are_valid(string $uri, string $role): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);

        $response = $this->actingAs($user)->get($uri);

        if ($response->getStatusCode() !== 200) {
            $this->assertTrue(true, 'Halaman tidak dirender (redirect), tidak ada link untuk diaudit.');

            return;
        }

        $html = $response->getContent();

        preg_match_all('/href="([^"]*(?:export|print|cetak|pdf|excel|import)[^"]*)"/i', $html, $matches);

        foreach (array_unique($matches[1] ?? []) as $href) {
            $href = html_entity_decode($href);
            $path = parse_url($href, PHP_URL_PATH);
            $query = parse_url($href, PHP_URL_QUERY);

            if (! $path || str_starts_with($href, '#')) {
                continue;
            }

            $target = $path.($query ? '?'.$query : '');

            // Hanya audit URL internal aplikasi.
            if (preg_match('#^https?://#i', $href) && ! str_contains($href, request()->getHost())) {
                continue;
            }

            $linkResponse = $this->actingAs($user)->get($target);
            $status = $linkResponse->getStatusCode();

            $this->assertNotSame(404, $status, "Link {$target} pada halaman {$uri} mengembalikan 404 Not Found.");
            $this->assertNotSame(500, $status, "Link {$target} pada halaman {$uri} mengembalikan 500 Server Error.");
        }

        $this->assertNotEmpty($html, "Halaman {$uri} tidak menghasilkan konten HTML.");
    }

    public function test_procurement_period_exports_show_friendly_error_instead_of_404(): void
    {
        $hr = User::factory()->create(['role' => 'hr', 'is_active' => true]);

        foreach (['/riwayat-pengadaan/cetak', '/riwayat-pengadaan/export/excel'] as $uri) {
            $response = $this->actingAs($hr)->get($uri);

            $this->assertSame(302, $response->getStatusCode(), "{$uri} harus redirect, bukan 404/500.");
            $response->assertSessionHas('error');
        }
    }

    public function test_excel_and_pdf_exports_show_friendly_error_instead_of_empty_file_or_404(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang', 'is_active' => true]);
        $hr = User::factory()->create(['role' => 'hr', 'is_active' => true]);
        $coil = User::factory()->create(['role' => User::ROLE_ADMIN_COIL, 'is_active' => true]);

        $cases = [
            [$gudang, '/gudang/history/export/excel'],
            [$gudang, '/gudang/location-change/export/excel'],
            [$gudang, '/gudang/movements/export/excel'],
            [$hr, '/hr/approval/export/excel'],
            [$hr, '/riwayat-pengadaan/export/excel'],
            [$coil, '/admin-coil/coil-tags/export'],
            [$coil, '/admin-coil/coil-tags/pdf'],
            [$coil, '/admin-coil/pergerakan-coil/export'],
        ];

        foreach ($cases as [$user, $uri]) {
            $response = $this->actingAs($user)->get($uri);

            $this->assertSame(302, $response->getStatusCode(), "{$uri} harus redirect dengan pesan error, bukan 404/500.");
            $response->assertSessionHas('error');
        }
    }

    public function test_import_preview_handles_missing_file_gracefully(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin/import/preview', []);

        $this->assertSame(302, $response->getStatusCode());
        $response->assertSessionHasErrors('file');
    }

    public function test_coil_location_import_preview_handles_missing_file_gracefully(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN_COIL, 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin-coil/lokasi/import/preview', []);

        $this->assertSame(302, $response->getStatusCode());
        $response->assertSessionHasErrors('file');
    }
}
