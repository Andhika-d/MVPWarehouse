<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_preview_limits_display_to_one_hundred_rows_but_shows_total(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $item = Item::create(['name' => 'Kertas Preview', 'unit' => 'rim', 'stock' => 20]);

        foreach (range(1, 101) as $index) {
            $stockRequest = StockRequest::create([
                'user_id' => $gudang->id,
                'item_id' => $item->id,
                'quantity' => $index,
                'unit' => 'rim',
                'priority' => 'Biasa',
                'reason' => 'Preview '.$index,
                'status' => 'Disetujui',
                'created_at' => now()->subMinutes(101 - $index),
            ]);
        }

        $response = $this->actingAs($gudang)
            ->get('/gudang/history/export/preview?status=Disetujui');

        $response->assertOk()
            ->assertSee('Menampilkan 100 dari 101 baris.')
            ->assertSee('/gudang/history/export/pdf?status=Disetujui', false)
            ->assertSee('/gudang/history/export/excel?status=Disetujui', false);

        $this->assertSame(100, substr_count($response->getContent(), '<tr class="align-top'));
    }

    public function test_movement_preview_keeps_export_filters_and_download_link(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $item = Item::create(['name' => 'Barang Masuk Preview', 'unit' => 'Pcs', 'stock' => 10]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'balance_after' => 10,
            'reason' => 'Penerimaan preview',
            'user_id' => $gudang->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($gudang)
            ->get('/gudang/movements/export/preview?export_type=in&item_id='.$item->id);

        $response->assertOk()
            ->assertSee('Barang Masuk Preview')
            ->assertSee('/gudang/movements/export/excel?export_type=in&amp;item_id='.$item->id, false);
    }

    public function test_admin_audit_preview_is_role_protected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gudang = User::factory()->create(['role' => 'gudang']);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'preview_test',
            'details' => 'Data khusus preview',
        ]);

        $this->actingAs($admin)
            ->get('/admin/audit/export/preview?action=preview_test')
            ->assertOk()
            ->assertSee('Data khusus preview')
            ->assertSee('/admin/audit/export/excel?action=preview_test', false);

        $this->actingAs($gudang)
            ->get('/admin/audit/export/preview')
            ->assertForbidden();
    }

    public function test_hr_history_and_exports_can_be_separated_by_nota_date(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $gudang = User::factory()->create(['role' => 'gudang']);
        $targetItem = Item::create(['name' => 'Barang Nota September', 'unit' => 'Pcs', 'stock' => 20]);
        $otherItem = Item::create(['name' => 'Barang Nota Agustus', 'unit' => 'Pcs', 'stock' => 20]);

        foreach (range(1, 4) as $index) {
            $stockRequest = StockRequest::create([
                'user_id' => $gudang->id,
                'item_id' => $targetItem->id,
                'quantity' => $index,
                'unit' => 'Pcs',
                'priority' => 'Biasa',
                'reason' => 'Nota September '.$index,
                'status' => 'Disetujui',
            ]);
            $stockRequest->forceFill([
                'created_at' => '2026-09-07 '.str_pad((string) $index, 2, '0', STR_PAD_LEFT).':00:00',
                'updated_at' => '2026-09-07 '.str_pad((string) $index, 2, '0', STR_PAD_LEFT).':00:00',
            ])->saveQuietly();
        }

        $otherRequest = StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $otherItem->id,
            'quantity' => 1,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Nota Agustus',
            'status' => 'Disetujui',
        ]);
        $otherRequest->forceFill([
            'created_at' => '2026-08-31 10:00:00',
            'updated_at' => '2026-08-31 10:00:00',
        ])->saveQuietly();

        $this->actingAs($hr)
            ->get('/hr/history?date=2026-09-07')
            ->assertOk()
            ->assertSee('#NOTA-20260907')
            ->assertSee('Barang Nota September')
            ->assertDontSee('Barang Nota Agustus');

        $this->get('/hr/history/export/preview?date=2026-09-07')
            ->assertOk()
            ->assertSee('Nota Pengadaan #NOTA-20260907')
            ->assertSee('Menampilkan 4 dari 4 baris.')
            ->assertDontSee('Barang Nota Agustus')
            ->assertSee('/hr/history/export/pdf?date=2026-09-07', false)
            ->assertSee('/hr/history/export/excel?date=2026-09-07', false);

        $this->get('/hr/history/export/pdf?date=2026-09-07')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=nota-pengadaan-20260907.pdf');

        $this->get('/hr/history/export/excel?date=2026-09-07')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="nota-pengadaan-20260907.xlsx"');
    }

    public function test_hr_approval_and_exports_can_be_filtered_by_nota_date(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $gudang = User::factory()->create(['role' => 'gudang']);
        $targetItem = Item::create(['name' => 'Approval Nota September', 'unit' => 'Pcs', 'stock' => 10]);
        $otherItem = Item::create(['name' => 'Approval Nota Agustus', 'unit' => 'Pcs', 'stock' => 10]);

        $targetRequest = StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $targetItem->id,
            'quantity' => 2,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Approval September',
            'status' => 'Menunggu Review',
        ]);
        $targetRequest->forceFill([
            'created_at' => '2026-09-07 09:00:00',
            'updated_at' => '2026-09-07 09:00:00',
        ])->saveQuietly();

        $otherRequest = StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $otherItem->id,
            'quantity' => 1,
            'unit' => 'Pcs',
            'priority' => 'Biasa',
            'reason' => 'Approval Agustus',
            'status' => 'Menunggu Review',
        ]);
        $otherRequest->forceFill([
            'created_at' => '2026-08-31 09:00:00',
            'updated_at' => '2026-08-31 09:00:00',
        ])->saveQuietly();

        $this->actingAs($hr)
            ->get('/hr/approval?date=2026-09-07')
            ->assertOk()
            ->assertSee('#NOTA-20260907')
            ->assertSee('Approval Nota September')
            ->assertDontSee('Approval Nota Agustus');

        $this->get('/hr/approval/export/preview?date=2026-09-07')
            ->assertOk()
            ->assertSee('Approval Nota #NOTA-20260907')
            ->assertSee('Menampilkan 1 dari 1 baris.')
            ->assertDontSee('Approval Nota Agustus')
            ->assertSee('/hr/approval/export/pdf?date=2026-09-07', false)
            ->assertSee('/hr/approval/export/excel?date=2026-09-07', false);

        $this->get('/hr/approval/export/pdf?date=2026-09-07')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=approval-nota-20260907.pdf');

        $this->get('/hr/approval/export/excel?date=2026-09-07')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="approval-nota-20260907.xlsx"');
    }
}
