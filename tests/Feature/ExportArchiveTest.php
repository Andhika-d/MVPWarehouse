<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExportArchiveTest extends TestCase
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

    protected function makeLocation(string $rack, int $number, ?string $subLocation = null): StorageLocation
    {
        $prefix = StorageLocation::getPrefixForRack($rack);

        return StorageLocation::create([
            'code' => $prefix.'-'.str_pad($number, 3, '0', STR_PAD_LEFT),
            'rack' => $rack,
            'number' => $number,
            'sub_location' => $subLocation,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);
    }

    protected function makeItem(StorageLocation $location): Item
    {
        $item = Item::create([
            'name' => 'Barang Arsip '.uniqid(),
            'storage_location_id' => $location->id,
            'stock' => 5,
            'unit' => 'Pcs',
        ]);

        $location->syncStatus();

        return $item;
    }

    protected function makeRequest(User $gudang, Item $item, StorageLocation $to, string $status): LocationChangeRequest
    {
        $from = $item->storageLocation;

        return LocationChangeRequest::create([
            'item_id' => $item->id,
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'from_sub_location' => $from->sub_location,
            'target_sub_location' => $to->sub_location,
            'requested_by' => $gudang->id,
            'status' => $status,
            'reason' => 'Butuh penyesuaian lokasi',
            'decided_at' => $status === LocationChangeRequest::STATUS_PENDING ? null : now(),
        ]);
    }

    private function extractXlsxText(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx-arsip');
        file_put_contents($tempFile, $content);
        $zip = new \ZipArchive();
        $zip->open($tempFile);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($tempFile);

        return $sheet ?? '';
    }

    public function test_gudang_can_export_location_changes_to_excel(): void
    {
        $gudang = $this->makeUser('gudang');
        $from = $this->makeLocation('A', 1, '1.1');
        $to = $this->makeLocation('A', 2, '1.2');
        $item = $this->makeItem($from);
        $this->makeRequest($gudang, $item, $to, LocationChangeRequest::STATUS_PENDING);

        $response = $this->actingAs($gudang)->get('/gudang/location-change/export/excel');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="riwayat-pengajuan-lokasi.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString($item->name, $sheet);
        $this->assertStringContainsString('1.2', $sheet);
    }

    public function test_gudang_can_export_location_changes_to_pdf(): void
    {
        $gudang = $this->makeUser('gudang');
        $from = $this->makeLocation('A', 1);
        $to = $this->makeLocation('B', 1);
        $item = $this->makeItem($from);
        $this->makeRequest($gudang, $item, $to, LocationChangeRequest::STATUS_PENDING);

        $response = $this->actingAs($gudang)->get('/gudang/location-change/export/pdf');
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_gudang_export_is_scoped_to_own_requests_only(): void
    {
        $gudangA = $this->makeUser('gudang');
        $gudangB = $this->makeUser('gudang', 'B');
        $fromA = $this->makeLocation('A', 1);
        $toA = $this->makeLocation('A', 2);
        $fromB = $this->makeLocation('A', 3);
        $toB = $this->makeLocation('A', 4);
        $itemB = $this->makeItem($fromB);
        $this->makeRequest($gudangA, $this->makeItem($fromA), $toA, LocationChangeRequest::STATUS_PENDING);
        $this->makeRequest($gudangB, $itemB, $toB, LocationChangeRequest::STATUS_PENDING);

        $response = $this->actingAs($gudangA)->get('/gudang/location-change/export/excel');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringNotContainsString($itemB->name, $sheet);
    }

    public function test_gudang_export_empty_returns_error(): void
    {
        $gudang = $this->makeUser('gudang');

        $response = $this->actingAs($gudang)
            ->from('/gudang/location-change')
            ->get('/gudang/location-change/export/excel');

        $response->assertRedirect('/gudang/location-change');
        $response->assertSessionHas('error');
    }

    public function test_admin_can_export_location_changes_to_pdf_and_excel(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');
        $from = $this->makeLocation('A', 1, '1.1');
        $to = $this->makeLocation('A', 2, '1.2');
        $item = $this->makeItem($from);
        $this->makeRequest($gudang, $item, $to, LocationChangeRequest::STATUS_APPROVED);

        $pdf = $this->actingAs($admin)->get(route('admin.location-changes.export-pdf'));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');

        $excel = $this->actingAs($admin)->get(route('admin.location-changes.export-excel'));
        $excel->assertOk();
        $excel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheet = $this->extractXlsxText($excel->getContent());
        $this->assertStringContainsString($item->name, $sheet);
    }

    public function test_admin_export_respects_status_filter(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');
        $fromA = $this->makeLocation('A', 1);
        $toA = $this->makeLocation('A', 2);
        $fromB = $this->makeLocation('B', 1);
        $toB = $this->makeLocation('B', 2);
        $approvedItem = $this->makeItem($fromA);
        $pendingItem = $this->makeItem($fromB);
        $this->makeRequest($gudang, $approvedItem, $toA, LocationChangeRequest::STATUS_APPROVED);
        $this->makeRequest($gudang, $pendingItem, $toB, LocationChangeRequest::STATUS_PENDING);

        $response = $this->actingAs($admin)->get(route('admin.location-changes.export-excel', ['status' => 'Disetujui']));
        $response->assertOk();

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString($approvedItem->name, $sheet);
        $this->assertStringNotContainsString($pendingItem->name, $sheet);
    }

    public function test_admin_can_export_audit_log_to_excel(): void
    {
        $admin = $this->makeUser('admin');
        $gudang = $this->makeUser('gudang');

        AuditLog::create([
            'user_id' => $gudang->id,
            'action' => 'location_change_submitted',
            'target_type' => LocationChangeRequest::class,
            'target_id' => 1,
            'details' => 'Pengajuan lokasi unik-arsip',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit.export-excel'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="audit-log.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Pengajuan lokasi unik-arsip', $sheet);
        $this->assertStringContainsString('location_change_submitted', $sheet);
        $this->assertStringContainsString($gudang->name, $sheet);
    }

    public function test_admin_audit_export_respects_action_filter(): void
    {
        $admin = $this->makeUser('admin');

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'login',
            'details' => 'Login berhasil',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'backup_created',
            'details' => 'Backup tersimpan',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit.export-excel', ['action' => 'backup_created']));
        $response->assertOk();

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Backup tersimpan', $sheet);
        $this->assertStringNotContainsString('Login berhasil', $sheet);
    }

    public function test_non_gudang_cannot_export_gudang_location_changes(): void
    {
        $hr = $this->makeUser('hr');

        $this->actingAs($hr)->get('/gudang/location-change/export/excel')->assertForbidden();
        $this->actingAs($hr)->get('/gudang/location-change/export/pdf')->assertForbidden();
    }

    public function test_non_admin_cannot_export_audit_log(): void
    {
        $gudang = $this->makeUser('gudang');

        $this->actingAs($gudang)->get(route('admin.audit.export-excel'))->assertForbidden();
    }
}