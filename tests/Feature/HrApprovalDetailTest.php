<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrApprovalDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Detail',
            'email' => $role.'-detail-'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'role' => $role,
        ]);
    }

    protected function makeRequest(?string $attachmentPath = null): StockRequest
    {
        $gudang = $this->makeUser('gudang');
        $item = Item::create([
            'name' => 'Kertas HVS A4',
            'rack_location' => 'B',
            'stock' => 5,
            'unit' => 'Rim',
        ]);

        return StockRequest::create([
            'user_id' => $gudang->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Rim',
            'priority' => 'Biasa',
            'reason' => 'Butuh untuk operasional kantor',
            'attachment_path' => $attachmentPath,
            'status' => 'Menunggu Review',
        ]);
    }

    public function test_hr_can_view_approval_detail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('attachments/foto.png', UploadedFile::fake()->create('foto.png', 10, 'image/png')->getContent());

        $hr = $this->makeUser('hr');
        $stockRequest = $this->makeRequest('attachments/foto.png');

        $response = $this->actingAs($hr)->get('/hr/requests/'.$stockRequest->id);

        $response->assertOk();
        $response->assertSee('Kertas HVS A4');
        $response->assertSee('Gudang Detail');
        $response->assertSee('Butuh untuk operasional kantor');
        $response->assertSee('Menunggu Review');
        $response->assertSee('/storage/attachments/foto.png');
    }

    public function test_hr_detail_requires_actionable_request(): void
    {
        $hr = $this->makeUser('hr');
        $stockRequest = $this->makeRequest();

        $stockRequest->update(['status' => 'Disetujui']);

        $this->actingAs($hr)->get('/hr/requests/'.$stockRequest->id)->assertForbidden();
    }

    public function test_gudang_cannot_access_hr_approval_detail(): void
    {
        $gudang = $this->makeUser('gudang');
        $stockRequest = $this->makeRequest();

        $this->actingAs($gudang)->get('/hr/requests/'.$stockRequest->id)->assertForbidden();
    }

    public function test_approval_page_shows_detail_link(): void
    {
        $hr = $this->makeUser('hr');
        $stockRequest = $this->makeRequest();

        $this->actingAs($hr)->get('/hr/approval')
            ->assertOk()
            ->assertSee('/hr/requests/'.$stockRequest->id);
    }
}
