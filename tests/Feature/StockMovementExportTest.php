<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Gudang Test',
            'email' => 'gudang-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'gudang',
        ]);
    }

    private function makeLocation(string $rack = 'A', int $number = 1): StorageLocation
    {
        $prefix = StorageLocation::getPrefixForRack($rack);

        return StorageLocation::create([
            'code' => $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT),
            'rack' => $rack,
            'number' => $number,
            'status' => StorageLocation::STATUS_EMPTY,
        ]);
    }

    private function makeItem(int $stock = 10, string $rack = 'A', int $number = 1): Item
    {
        $location = $this->makeLocation($rack, $number);

        return Item::create([
            'name' => 'Barang Test ' . uniqid(),
            'storage_location_id' => $location->id,
            'stock' => $stock,
            'unit' => 'Pcs',
        ]);
    }

    private function extractXlsxText(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx-test');
        file_put_contents($tempFile, $content);
        $zip = new \ZipArchive();
        $zip->open($tempFile);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($tempFile);

        return $sheet ?? '';
    }

    public function test_export_in_movements_only(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem();

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 5,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan',
            'user_id' => $user->id,
            'balance_after' => 15,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'OUT',
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Pemakaian',
            'user_id' => $user->id,
            'balance_after' => 12,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=in');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="barang-masuk.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Penerimaan', $sheet);
        $this->assertStringNotContainsString('Pemakaian', $sheet);
    }

    public function test_export_out_movements_only(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem();

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'OUT',
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Pemakaian produksi',
            'user_id' => $user->id,
            'balance_after' => 7,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=out');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="barang-keluar.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Pemakaian produksi', $sheet);
        $this->assertStringNotContainsString('Penerimaan', $sheet);
    }

    public function test_export_in_out_movements_combined(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem();

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'OUT',
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Pemakaian',
            'user_id' => $user->id,
            'balance_after' => 7,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'ADJUSTMENT',
            'quantity' => 2,
            'unit' => 'Pcs',
            'reason' => 'Koreksi stok',
            'user_id' => $user->id,
            'balance_after' => 9,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=in_out');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="barang-masuk-keluar.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Penerimaan', $sheet);
        $this->assertStringContainsString('Pemakaian', $sheet);
        $this->assertStringNotContainsString('Koreksi stok', $sheet);
    }

    public function test_export_adjustment_only(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem();

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'ADJUSTMENT',
            'quantity' => 5,
            'unit' => 'Pcs',
            'reason' => 'Stok awal import',
            'user_id' => $user->id,
            'balance_after' => 15,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=adjustment');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="penyesuaian-stok.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Stok awal import', $sheet);
        $this->assertStringNotContainsString('Penerimaan', $sheet);
    }

    public function test_export_all_movements(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem();

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'ADJUSTMENT',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Stok awal import',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now()->subDays(2),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 5,
            'unit' => 'Pcs',
            'reason' => 'Penerimaan barang',
            'user_id' => $user->id,
            'balance_after' => 15,
            'occurred_at' => now()->subDay(),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'OUT',
            'quantity' => 3,
            'unit' => 'Pcs',
            'reason' => 'Pemakaian produksi',
            'user_id' => $user->id,
            'balance_after' => 12,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=all');
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="semua-perubahan-stok.xlsx"');

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Stok awal import', $sheet);
        $this->assertStringContainsString('Penerimaan barang', $sheet);
        $this->assertStringContainsString('Pemakaian produksi', $sheet);
    }

    public function test_export_respects_item_filter(): void
    {
        $user = $this->makeUser();
        $item1 = $this->makeItem(10, 'A', 1);
        $item2 = $this->makeItem(10, 'B', 1);

        StockMovement::create([
            'item_id' => $item1->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Item satu',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now(),
        ]);
        StockMovement::create([
            'item_id' => $item2->id,
            'type' => 'IN',
            'quantity' => 5,
            'unit' => 'Pcs',
            'reason' => 'Item dua',
            'user_id' => $user->id,
            'balance_after' => 5,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get("/gudang/movements/export/excel?export_type=in&item_id={$item1->id}");
        $response->assertStatus(200);

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Item satu', $sheet);
        $this->assertStringNotContainsString('Item dua', $sheet);
    }

    public function test_export_respects_date_filter(): void
    {
        $user = $this->makeUser();
        $item = $this->makeItem(10, 'A', 1);

        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 10,
            'unit' => 'Pcs',
            'reason' => 'Baru kemarin',
            'user_id' => $user->id,
            'balance_after' => 10,
            'occurred_at' => now()->subDays(5),
        ]);
        StockMovement::create([
            'item_id' => $item->id,
            'type' => 'IN',
            'quantity' => 5,
            'unit' => 'Pcs',
            'reason' => 'Hari ini',
            'user_id' => $user->id,
            'balance_after' => 5,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get("/gudang/movements/export/excel?export_type=in&date_from=" . now()->subDay()->format('Y-m-d'));
        $response->assertStatus(200);

        $sheet = $this->extractXlsxText($response->getContent());
        $this->assertStringContainsString('Hari ini', $sheet);
        $this->assertStringNotContainsString('Baru kemarin', $sheet);
    }

    public function test_export_empty_data_returns_error(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=in');
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_export_requires_export_type(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel');
        $response->assertStatus(302);
    }

    public function test_export_invalid_type_rejected(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/gudang/movements/export/excel?export_type=invalid');
        $response->assertStatus(302);
    }
}
