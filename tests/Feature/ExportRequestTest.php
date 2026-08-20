<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_can_export_history_to_pdf_and_excel(): void
    {
        $user = User::factory()->create(['role' => 'gudang']);
        $item = Item::create([
            'name' => 'Kertas A4',
            'unit' => 'rim',
            'stock' => 20,
            'rack_location' => 'A',
        ]);

        StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'unit' => 'rim',
            'priority' => 'Biasa',
            'reason' => 'Butuh tambahan',
            'status' => 'Disetujui',
        ]);

        $this->actingAs($user);

        $pdfResponse = $this->get('/gudang/history/export/pdf');
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');

        $excelResponse = $this->get('/gudang/history/export/excel');
        $excelResponse->assertOk();
        $excelResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
