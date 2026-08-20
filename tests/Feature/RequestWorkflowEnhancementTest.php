<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RequestWorkflowEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_request_shows_review_note_in_shopping_list(): void
    {
        $hr = User::create([
            'name' => 'HR Review',
            'email' => 'hr-review@example.com',
            'password' => Hash::make('password'),
            'role' => 'hr',
        ]);

        $item = Item::create([
            'name' => 'Mouse Wireless',
            'rack_location' => 'C',
            'stock' => 3,
            'unit' => 'Pcs',
        ]);

        $request = StockRequest::create([
            'user_id' => 1,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Pcs',
            'priority' => 'Mendesak',
            'reason' => 'Butuh cepat',
            'status' => 'Disetujui',
            'review_note' => 'Disetujui sesuai kebutuhan',
        ]);

        $response = $this->actingAs($hr)->get('/hr/daftar-belanja');

        $response->assertSee('Disetujui sesuai kebutuhan');
    }
}
