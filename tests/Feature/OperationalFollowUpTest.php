<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_view_read_only_movements_filtered_by_period(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $item = Item::create(['name' => 'Barang Dalam Periode', 'unit' => 'Pcs', 'stock' => 10]);
        $outside = Item::create(['name' => 'Barang Luar Periode', 'unit' => 'Pcs', 'stock' => 10]);

        $this->movement($item, $hr, '2026-08-10 10:00:00');
        $this->movement($outside, $hr, '2026-08-20 10:00:00');

        $this->actingAs($hr)
            ->get('/hr/movements?period_mode=flexible&period_start=2026-08-07&period_end=2026-08-14')
            ->assertOk()
            ->assertSee('Barang Dalam Periode')
            ->assertDontSee('Barang Luar Periode')
            ->assertSee('Perubahan Stok');
    }

    public function test_request_history_and_preview_share_period_filter(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $inside = Item::create(['name' => 'Request Dalam Periode', 'unit' => 'Pcs', 'stock' => 1]);
        $outside = Item::create(['name' => 'Request Luar Periode', 'unit' => 'Pcs', 'stock' => 1]);
        $this->stockRequest($gudang, $inside, '2026-08-10 09:00:00');
        $this->stockRequest($gudang, $outside, '2026-08-20 09:00:00');
        $query = 'period_mode=flexible&period_start=2026-08-07&period_end=2026-08-14';

        foreach (['/gudang/history?', '/gudang/history/export/preview?'] as $path) {
            $this->actingAs($gudang)
                ->get($path.$query)
                ->assertOk()
                ->assertSee('Request Dalam Periode')
                ->assertDontSee('Request Luar Periode');
        }
    }

    public function test_director_all_requests_can_be_filtered_by_period(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        $requester = User::factory()->create(['role' => 'gudang']);
        $inside = Item::create(['name' => 'Request Direktur Dalam Periode', 'unit' => 'Pcs', 'stock' => 1]);
        $outside = Item::create(['name' => 'Request Direktur Luar Periode', 'unit' => 'Pcs', 'stock' => 1]);
        $this->stockRequest($requester, $inside, '2026-08-10 09:00:00');
        $this->stockRequest($requester, $outside, '2026-08-20 09:00:00');

        $this->actingAs($director)
            ->get('/director/requests?period_mode=flexible&period_start=2026-08-07&period_end=2026-08-14')
            ->assertOk()
            ->assertSee('Request Direktur Dalam Periode')
            ->assertDontSee('Request Direktur Luar Periode')
            ->assertSee('name="period_start" value="2026-08-07"', false);
    }

    public function test_receipt_queue_does_not_hide_old_actionable_requests_by_month(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        $item = Item::create(['name' => 'Request Lama Tetap Terlihat', 'unit' => 'Pcs', 'stock' => 1]);
        $this->stockRequest($gudang, $item, '2025-01-10 09:00:00');

        $this->actingAs($gudang)
            ->get('/gudang/penerimaan?month=2026-09')
            ->assertOk()
            ->assertSee('Request Lama Tetap Terlihat')
            ->assertDontSee('type="month"', false);
    }

    public function test_stock_hard_copy_contains_all_filtered_rows_and_a4_approval_box(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang', 'name' => 'Petugas Cetak']);

        foreach (range(1, 21) as $number) {
            $location = StorageLocation::create([
                'code' => 'RA-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'rack' => 'A',
                'number' => $number,
                'sub_location' => 'a.1.'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'status' => StorageLocation::STATUS_OCCUPIED,
            ]);
            Item::create([
                'name' => 'Barang Cetak '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'size' => $number === 1 ? 'Besar' : null,
                'storage_location_id' => $location->id,
                'unit' => 'Pcs',
                'stock' => $number,
            ]);
        }

        $this->actingAs($gudang)
            ->get('/gudang/stock?rack=A')
            ->assertOk()
            ->assertSee('Hard Copy')
            ->assertDontSee('Cetak Landscape')
            ->assertDontSee('Cetak Portrait');

        $this->actingAs($gudang)
            ->get('/gudang/stock/print?rack=A')
            ->assertOk()
            ->assertSee('Barang Cetak 01')
            ->assertSee('Barang Cetak 21')
            ->assertSee('Petugas Cetak')
            ->assertSee('size: A4 landscape', false)
            ->assertSee('Made')
            ->assertSee('작 성')
            ->assertSee('Check')
            ->assertSee('검 토')
            ->assertSee('Approve')
            ->assertSee('승 인')
            ->assertSee('width: 78mm', false)
            ->assertSee('height: 28mm', false)
            ->assertSee('table-header-group', false);

        $this->actingAs($gudang)
            ->get('/gudang/stock/print?rack=A&orientation=portrait')
            ->assertOk()
            ->assertSee('size: A4 portrait', false)
            ->assertSee('stock-table--portrait', false)
            ->assertSee('<th>Lokasi</th>', false)
            ->assertSee('RA-001')
            ->assertSee('Rak A')
            ->assertSee('Sub: a.1.01')
            ->assertSee('Barang Cetak 01')
            ->assertSee('Ukuran: Besar')
            ->assertSee('Pcs')
            ->assertSee('<th>Kode Tag</th>', false);
    }

    public function test_company_timeline_has_real_pagination(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        $item = Item::create(['name' => 'Barang Timeline', 'unit' => 'Pcs', 'stock' => 100]);

        foreach (range(1, 51) as $index) {
            $this->movement($item, $director, now()->subMinutes(51 - $index)->toDateTimeString(), 'Aktivitas '.$index);
        }

        $this->actingAs($director)
            ->get('/director/timeline?page=2')
            ->assertOk()
            ->assertSee('Aktivitas 1')
            ->assertDontSee('Aktivitas 51');
    }

    private function movement(Item $item, User $user, string $occurredAt, string $reason = 'Perubahan stok'): StockMovement
    {
        return StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_OUT,
            'quantity' => 1,
            'unit' => $item->unit,
            'balance_before' => $item->stock,
            'balance_after' => $item->stock - 1,
            'reason' => $reason,
            'user_id' => $user->id,
            'occurred_at' => $occurredAt,
        ]);
    }

    private function stockRequest(User $user, Item $item, string $createdAt): StockRequest
    {
        $request = StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => 1,
            'unit' => $item->unit,
            'priority' => 'Biasa',
            'reason' => 'Pengujian periode',
            'status' => 'Disetujui',
        ]);

        $request->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $request;
    }
}
