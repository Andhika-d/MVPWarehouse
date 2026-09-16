<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\LocationChangeRequest;
use App\Models\MonitoringIssue;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StorageLocation;
use App\Models\User;
use App\Services\Monitoring\MonitoringIssueSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_detector_creates_warning_issue_after_three_days(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 73, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'request_awaiting_review:stockrequest:' . $request->id)->first();

        $this->assertNotNull($issue);
        $this->assertSame(MonitoringIssue::SOURCE_DETECTOR, $issue->source);
        $this->assertSame(MonitoringIssue::RULE_REQUEST_AWAITING_REVIEW, $issue->rule_key);
        $this->assertSame('Request Menunggu Review', $issue->category);
        $this->assertSame(MonitoringIssue::SEVERITY_WARNING, $issue->severity);
        $this->assertSame(MonitoringIssue::STATUS_OPEN, $issue->status);
        $this->assertSame(StockRequest::class, $issue->subject_type);
        $this->assertSame($request->id, $issue->subject_id);
        $this->assertSame(1, $issue->occurrence_count);
        $this->assertNotNull($issue->detected_at);
        $this->assertNotNull($issue->last_seen_at);
        $this->assertStringContainsString('3 hari', $issue->description);
        $this->assertStringContainsString($item->name, $issue->description);
    }

    public function test_no_issue_under_72_hours_threshold(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $this->request($requester, $item, 71, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $this->assertSame(0, MonitoringIssue::count());
    }

    public function test_issue_escalates_to_critical_after_seven_days(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 169, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'request_awaiting_review:stockrequest:' . $request->id)->first();

        $this->assertSame(MonitoringIssue::SEVERITY_CRITICAL, $issue->severity);
        $this->assertStringContainsString('7 hari', $issue->description);
    }

    public function test_sync_is_idempotent_and_does_not_duplicate(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $this->request($requester, $item, 90, 'Menunggu Review');

        $synchronizer = new MonitoringIssueSynchronizer();
        $synchronizer->sync();
        $synchronizer->sync();

        $this->assertSame(1, MonitoringIssue::count());
        $issue = MonitoringIssue::first();
        $this->assertSame(1, $issue->occurrence_count);
    }

    public function test_resolved_issue_reopens_with_incremented_occurrence_when_delay_recurs(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 73, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'request_awaiting_review:stockrequest:' . $request->id)->first();
        $issue->update([
            'status' => MonitoringIssue::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);

        (new MonitoringIssueSynchronizer())->sync();

        $issue->refresh();
        $this->assertSame(MonitoringIssue::STATUS_OPEN, $issue->status);
        $this->assertNull($issue->resolved_at);
        $this->assertSame(2, $issue->occurrence_count);
    }

    public function test_open_issue_auto_resolves_when_request_gets_reviewed(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 100, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'request_awaiting_review:stockrequest:' . $request->id)->first();
        $this->assertSame(MonitoringIssue::STATUS_OPEN, $issue->status);

        $request->update([
            'status' => 'Disetujui',
            'approved_at' => now(),
        ]);
        $request->requestHistories()->create([
            'user_id' => $requester->id,
            'status' => 'Disetujui',
            'note' => 'Disetujui oleh HR',
        ]);

        (new MonitoringIssueSynchronizer())->sync();

        $issue->refresh();
        $this->assertSame(MonitoringIssue::STATUS_RESOLVED, $issue->status);
        $this->assertNotNull($issue->resolved_at);
        $this->assertSame('Otomatis: kondisi tidak lagi terdeteksi.', $issue->context['resolution']);
        $this->assertSame(1, MonitoringIssue::count());
    }

    public function test_detector_finds_awaiting_first_receipt_after_approval(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 150, 'Disetujui', received: 0, approvedHoursAgo: 90);

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'awaiting_first_receipt:stockrequest:' . $request->id)->first();

        $this->assertNotNull($issue);
        $this->assertSame('Menunggu Penerimaan Awal', $issue->category);
        $this->assertSame(MonitoringIssue::SEVERITY_WARNING, $issue->severity);
        $this->assertSame(MonitoringIssue::RULE_AWAITING_FIRST_RECEIPT, $issue->rule_key);
        $this->assertStringContainsString('Belum ada penerimaan barang', $issue->description);
        $this->assertSame(1, MonitoringIssue::count());
    }

    public function test_detector_finds_stalled_partial_receipt_from_last_movement(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $request = $this->request($requester, $item, 200, 'Sebagian Diterima', received: 2);

        StockMovement::create([
            'item_id' => $item->id,
            'type' => StockMovement::TYPE_IN,
            'quantity' => 2,
            'unit' => $item->unit,
            'reason' => 'Penerimaan barang dari pembelian',
            'stock_request_id' => $request->id,
            'user_id' => $requester->id,
            'balance_before' => 0,
            'balance_after' => 2,
            'occurred_at' => now()->subHours(100),
        ]);

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'partial_receipt_stalled:stockrequest:' . $request->id)->first();

        $this->assertNotNull($issue);
        $this->assertSame('Penerimaan Parsial Berhenti', $issue->category);
        $this->assertSame(MonitoringIssue::RULE_PARTIAL_RECEIPT_STALLED, $issue->rule_key);
        $this->assertStringContainsString('Penerimaan barang tidak berlanjut', $issue->description);
        $this->assertSame(MonitoringIssue::SEVERITY_WARNING, $issue->severity);
        $this->assertSame(1, MonitoringIssue::count());
    }

    public function test_detector_finds_location_change_awaiting_confirmation(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $from = $this->location('R-001', 1);
        $to = $this->location('R-002', 2);
        $item = Item::create(['name' => 'Kabel', 'unit' => 'Roll', 'stock' => 5, 'storage_location_id' => $from->id]);

        $change = LocationChangeRequest::create([
            'item_id' => $item->id,
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'requested_by' => $requester->id,
            'status' => LocationChangeRequest::STATUS_PENDING,
            'reason' => 'Penyusunan ulang gudang',
        ]);
        $createdAt = now()->subHours(90);
        $change->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        (new MonitoringIssueSynchronizer())->sync();

        $issue = MonitoringIssue::where('dedupe_key', 'location_change_awaiting_confirmation:locationchangerequest:' . $change->id)->first();

        $this->assertNotNull($issue);
        $this->assertSame('Perubahan Lokasi Menunggu Konfirmasi', $issue->category);
        $this->assertSame(MonitoringIssue::RULE_LOCATION_CHANGE_AWAITING_CONFIRMATION, $issue->rule_key);
        $this->assertStringContainsString($item->name, $issue->description);
        $this->assertStringContainsString('belum dikonfirmasi', $issue->description);
        $this->assertSame(1, MonitoringIssue::count());
    }

    public function test_console_command_creates_issues_and_is_locked(): void
    {
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item();
        $this->request($requester, $item, 80, 'Menunggu Review');

        $this->artisan('monitoring:sync-issues')
            ->assertSuccessful();

        $this->assertSame(1, MonitoringIssue::count());
    }

    public function test_director_issues_page_shows_summary_and_issue(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        $requester = User::factory()->create(['role' => 'gudang']);
        $item = $this->item('Sarung Tangan');
        $request = $this->request($requester, $item, 80, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $issuedAt = MonitoringIssue::first();

        $this->actingAs($director)
            ->get('/director/issues')
            ->assertOk()
            ->assertSee('Issue Aktif')
            ->assertSee('Peringatan')
            ->assertSee('Riwayat Selesai')
            ->assertSee('Request Menunggu Review')
            ->assertSee('Sarung Tangan')
            ->assertSee('Lihat detail request')
            ->assertSee($issuedAt->description);
    }

    public function test_director_issues_page_filters_by_status_and_severity(): void
    {
        $director = User::factory()->create(['role' => 'director']);
        $requester = User::factory()->create(['role' => 'gudang']);

        $item = $this->item('Barang Peringatan');
        $this->request($requester, $item, 80, 'Menunggu Review');

        (new MonitoringIssueSynchronizer())->sync();

        $this->actingAs($director)
            ->get('/director/issues?severity=' . urlencode('Kritis'))
            ->assertOk()
            ->assertDontSee('Barang Peringatan');

        $this->actingAs($director)
            ->get('/director/issues?status=Open')
            ->assertOk()
            ->assertSee('Barang Peringatan');
    }

    private function item(string $name = 'Sarung Tangan'): Item
    {
        return Item::create(['name' => $name, 'unit' => 'Pcs', 'stock' => 100]);
    }

    private function location(string $code, int $number): StorageLocation
    {
        return StorageLocation::create([
            'code' => $code,
            'rack' => 'A',
            'number' => $number,
            'sub_location' => 'a.1.' . str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'status' => StorageLocation::STATUS_OCCUPIED,
        ]);
    }

    private function request(User $user, Item $item, int $hoursAgo, string $status, int $received = 0, ?int $approvedHoursAgo = null): StockRequest
    {
        $request = StockRequest::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => 10,
            'unit' => $item->unit,
            'priority' => 'Biasa',
            'reason' => 'Pengujian monitoring',
            'status' => $status,
            'received_quantity' => $received,
        ]);

        $createdAt = now()->subHours($hoursAgo);
        $request->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        if ($approvedHoursAgo !== null) {
            $request->approved_at = now()->subHours($approvedHoursAgo);
        }

        $request->saveQuietly();

        return $request->refresh();
    }
}