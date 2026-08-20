<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_can_upload_attachment_when_submitting_request(): void
    {
        Storage::fake('public');

        $gudang = User::create([
            'name' => 'Gudang Upload',
            'email' => 'gudang-upload@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $item = Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        $response = $this->actingAs($gudang)->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => 2,
            'priority' => 'Biasa',
            'reason' => 'Butuh lampiran',
            'attachment' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect('/gudang/history');

        $stockRequest = StockRequest::latest()->first();
        $this->assertNotNull($stockRequest);
        $this->assertNotNull($stockRequest->attachment_path);
        $this->assertStringStartsWith('attachments/', $stockRequest->attachment_path);
    }

    public function test_dangerous_attachment_is_rejected(): void
    {
        $gudang = User::create([
            'name' => 'Gudang Bahaya',
            'email' => 'gudang-bahaya@example.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        $item = Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'A',
            'stock' => 3,
            'unit' => 'Rim',
        ]);

        $response = $this->actingAs($gudang)->post('/gudang/request-barang', [
            'item_id' => $item->id,
            'quantity' => 1,
            'priority' => 'Biasa',
            'reason' => 'uji keamanan',
            'attachment' => UploadedFile::fake()->create('script.php', 100, 'application/x-httpd-php'),
        ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('stock_requests', 0);
    }
}
