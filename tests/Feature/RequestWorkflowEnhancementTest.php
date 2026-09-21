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

    public function test_approved_request_shows_review_note_in_procurement_history(): void
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

        $note = \App\Models\ProcurementNote::findOrCreateForDate(now());
        StockRequest::create([
            'user_id' => 1,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'Pcs',
            'priority' => 'Mendesak',
            'reason' => 'Butuh cepat',
            'status' => 'Disetujui',
            'review_note' => 'Disetujui sesuai kebutuhan',
            'procurement_note_id' => $note->id,
        ]);

        $response = $this->actingAs($hr)->get(route('procurement-notes.show', $note));

        $response->assertSee('Disetujui sesuai kebutuhan');
    }
}
