<?php

namespace Tests\Feature;

use App\Models\CoilTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = User::factory()->create(['role' => User::ROLE_CHECKER]);
        $this->actingAs($this->checker);
    }

    public function test_checker_starts_ready_for_kp_with_autofocused_input(): void
    {
        $this->get('/checker')
            ->assertOk()
            ->assertSee('SIAP SCAN KP')
            ->assertSee('name="raw_kp"', false)
            ->assertSee('autofocus', false);
    }

    public function test_valid_kp_scan_shows_preview_and_switches_to_taehang(): void
    {
        $this->post('/checker/kp', ['raw_kp' => $this->validKp()])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('SIAP SCAN TAEHANG')
            ->assertSee('HKG0268301')
            ->assertSee('SNI 8784:2024 B')
            ->assertSee('1.9 × 1195')
            ->assertSee('name="raw_taehang"', false)
            ->assertSee('autofocus', false);
    }

    public function test_invalid_kp_scan_stays_ready_for_kp_and_shows_clear_error(): void
    {
        $this->from('/checker')
            ->post('/checker/kp', ['raw_kp' => '0,HKG0268301,SNI 8784:2024 B,0,1195,0,11.730,PTKP,0,'])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('SIAP SCAN KP')
            ->assertSee('Tebal harus berupa angka lebih besar dari 0.')
            ->assertDontSee('SIAP SCAN TAEHANG');
    }

    public function test_valid_taehang_scan_shows_combined_preview_before_saving(): void
    {
        $this->scanValidKp();

        $this->post('/checker/taehang', ['raw_taehang' => '09-020'])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('DATA SCAN LENGKAP')
            ->assertSee('SIMPAN DATA')
            ->assertSee('09-020')
            ->assertSee('Lokasi')
            ->assertDontSee('Data berhasil disimpan');

        $this->assertDatabaseCount('coil_tags', 0);
    }

    public function test_invalid_taehang_scan_keeps_kp_preview_and_requests_another_scan(): void
    {
        $this->scanValidKp();

        $this->from('/checker')
            ->post('/checker/taehang', ['raw_taehang' => '09_020'])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('SIAP SCAN TAEHANG')
            ->assertSee('Format QR Taehang harus 2 digit, tanda hubung, lalu 3 digit (contoh: 09-020).')
            ->assertSee('HKG0268301')
            ->assertDontSee('SIMPAN DATA');
    }

    public function test_successful_save_preserves_raw_scans_records_user_and_resets_to_kp(): void
    {
        $rawKp = '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,';
        $rawTaehang = '09-020';

        $this->post('/checker/kp', ['raw_kp' => $rawKp])->assertRedirect('/checker');
        $this->post('/checker/taehang', ['raw_taehang' => $rawTaehang])->assertRedirect('/checker');
        $this->post('/checker/save')->assertRedirect('/checker');

        $this->assertDatabaseHas('coil_tags', [
            'nomor_coil' => 'HKG0268301',
            'grade' => 'SNI 8784:2024 B',
            'tebal' => 1.9,
            'lebar' => 1195,
            'ukuran' => '1.9 × 1195',
            'berat' => 11.73,
            'company' => 'PTKP',
            'tag_taehang' => '09-020',
            'lokasi_coil' => null,
            'user_id' => $this->checker->id,
            'raw_kp' => $rawKp,
            'raw_taehang' => $rawTaehang,
        ]);
        $this->assertDatabaseCount('coil_tags', 1);

        $this->get('/checker')
            ->assertSee('Data berhasil disimpan.')
            ->assertSee('SIAP SCAN KP')
            ->assertSee('name="raw_kp"', false)
            ->assertSee('autofocus', false)
            ->assertDontSee('SIAP SCAN TAEHANG');
    }

    public function test_duplicate_coil_is_rejected_without_overwriting_existing_record(): void
    {
        $existing = CoilTag::factory()->create([
            'nomor_coil' => 'HKG0268301',
            'grade' => 'GRADE EXISTING',
            'tag_taehang' => '09-020',
            'raw_kp' => 'existing raw kp',
        ]);

        $this->from('/checker')
            ->post('/checker/kp', ['raw_kp' => $this->validKp()])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('Nomor coil HKG0268301 sudah tersimpan.')
            ->assertSee('SIAP SCAN KP')
            ->assertDontSee('SIAP SCAN TAEHANG');

        $this->assertDatabaseCount('coil_tags', 1);
        $this->assertDatabaseHas('coil_tags', [
            'id' => $existing->id,
            'grade' => 'GRADE EXISTING',
            'raw_kp' => 'existing raw kp',
        ]);
    }

    public function test_duplicate_taehang_tag_is_rejected_without_overwriting_existing_record(): void
    {
        $existing = CoilTag::factory()->create([
            'nomor_coil' => 'HKD0215600008',
            'tag_taehang' => '09-020',
            'raw_taehang' => 'original QR',
        ]);

        $this->scanValidKp();
        $this->from('/checker')
            ->post('/checker/taehang', ['raw_taehang' => '09-020'])
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('Tag Taehang 09-020 sudah tersimpan.')
            ->assertSee('SIAP SCAN TAEHANG')
            ->assertDontSee('SIMPAN DATA');

        $this->assertDatabaseCount('coil_tags', 1);
        $this->assertDatabaseHas('coil_tags', [
            'id' => $existing->id,
            'nomor_coil' => 'HKD0215600008',
            'raw_taehang' => 'original QR',
        ]);
    }

    public function test_save_rechecks_for_duplicate_coil_created_after_scan(): void
    {
        $this->scanValidKp();
        $this->post('/checker/taehang', ['raw_taehang' => '09-020']);

        CoilTag::factory()->create([
            'nomor_coil' => 'HKG0268301',
            'tag_taehang' => '01-001',
        ]);

        $this->from('/checker')
            ->post('/checker/save')
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('Nomor coil HKG0268301 sudah tersimpan.')
            ->assertSee('SIAP SCAN KP')
            ->assertDontSee('SIMPAN DATA');

        $this->assertDatabaseCount('coil_tags', 1);
    }

    public function test_save_rechecks_for_duplicate_tag_created_after_scan(): void
    {
        $this->scanValidKp();
        $this->post('/checker/taehang', ['raw_taehang' => '09-020']);

        CoilTag::factory()->create([
            'nomor_coil' => 'HKD0215600008',
            'tag_taehang' => '09-020',
        ]);

        $this->from('/checker')
            ->post('/checker/save')
            ->assertRedirect('/checker');

        $this->get('/checker')
            ->assertSee('Tag Taehang 09-020 sudah tersimpan.')
            ->assertSee('SIAP SCAN TAEHANG')
            ->assertDontSee('SIMPAN DATA');

        $this->assertDatabaseCount('coil_tags', 1);
    }

    public function test_only_checker_role_may_access_the_checker_flow(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gudang']))
            ->get('/checker')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN_COIL]))
            ->get('/checker')->assertForbidden();
    }

    public function test_migration_keeps_all_scan_fields_and_unique_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('coil_tags', [
            'nomor_coil', 'grade', 'tebal', 'lebar', 'ukuran', 'berat', 'company',
            'field_1', 'field_6', 'field_9', 'tag_taehang', 'lokasi_coil', 'user_id',
            'raw_kp', 'raw_taehang', 'scanned_at', 'created_at', 'updated_at',
        ]));

        $uniqueIndexes = collect(Schema::getIndexes('coil_tags'))
            ->filter(fn (array $index): bool => $index['unique'])
            ->pluck('columns')
            ->all();

        $this->assertContains(['nomor_coil'], $uniqueIndexes);
        $this->assertContains(['tag_taehang'], $uniqueIndexes);
    }

    private function scanValidKp(): TestResponse
    {
        return $this->post('/checker/kp', ['raw_kp' => $this->validKp()]);
    }

    private function validKp(): string
    {
        return '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,';
    }
}
