<?php

namespace Tests\Feature;

use App\Models\CoilLocation;
use App\Models\CoilTag;
use App\Models\CoilTagLocationHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class CoilLocationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function createCoilTag(array $attributes = []): CoilTag
    {
        return CoilTag::factory()->create(array_replace([
            'nomor_coil' => 'HKG0268301',
            'tag_taehang' => '09-020',
        ], $attributes));
    }

    public function test_migrations_seed_default_master_locations_and_history_table(): void
    {
        $this->assertSame(
            ['AL', 'AR', 'BL', 'BR', 'CL', 'CR'],
            CoilLocation::orderBy('urutan')->pluck('kode')->all(),
        );

        $this->assertTrue(Schema::hasColumns('coil_tag_location_histories', [
            'coil_tag_id', 'from_location', 'to_location', 'source', 'changed_by', 'note',
            'created_at', 'updated_at',
        ]));
    }

    public function test_checker_can_save_with_optional_manual_location(): void
    {
        $checker = $this->makeUser(User::ROLE_CHECKER);
        $this->actingAs($checker);

        $this->post('/checker/kp', ['raw_kp' => '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,']);
        $this->post('/checker/taehang', ['raw_taehang' => '09-020']);
        $this->post('/checker/save', ['lokasi_coil' => '  al '])->assertRedirect('/checker');

        $coilTag = CoilTag::firstOrFail();
        $this->assertSame('AL', $coilTag->lokasi_coil);

        $this->assertDatabaseHas('coil_tag_location_histories', [
            'coil_tag_id' => $coilTag->id,
            'from_location' => null,
            'to_location' => 'AL',
            'source' => CoilTagLocationHistory::SOURCE_CHECKER,
            'changed_by' => $checker->id,
        ]);
    }

    public function test_checker_without_location_keeps_it_null_without_history(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_CHECKER));

        $this->post('/checker/kp', ['raw_kp' => '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,']);
        $this->post('/checker/taehang', ['raw_taehang' => '09-020']);
        $this->post('/checker/save')->assertRedirect('/checker');

        $this->assertDatabaseHas('coil_tags', ['nomor_coil' => 'HKG0268301', 'lokasi_coil' => null]);
        $this->assertDatabaseCount('coil_tag_location_histories', 0);
    }

    public function test_checker_page_offers_master_locations_as_suggestions(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_CHECKER));

        $this->post('/checker/kp', ['raw_kp' => '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,']);
        $this->post('/checker/taehang', ['raw_taehang' => '09-020']);

        $this->get('/checker')
            ->assertOk()
            ->assertSee('name="lokasi_coil"', false)
            ->assertSee('coilLocationOptions', false)
            ->assertSee('<option value="AL">', false)
            ->assertSee('<option value="CR">', false);
    }

    public function test_admin_can_update_location_manually_and_history_is_recorded(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN_COIL);
        $record = $this->createCoilTag(['lokasi_coil' => null]);

        $this->actingAs($admin)
            ->from('/admin-coil/coil-tags/'.$record->id)
            ->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => 'bl'])
            ->assertRedirect('/admin-coil/coil-tags/'.$record->id);

        $this->assertSame('BL', $record->fresh()->lokasi_coil);
        $this->assertDatabaseHas('coil_tag_location_histories', [
            'coil_tag_id' => $record->id,
            'from_location' => null,
            'to_location' => 'BL',
            'source' => CoilTagLocationHistory::SOURCE_ADMIN_MANUAL,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_admin_clearing_location_records_history(): void
    {
        $record = $this->createCoilTag(['lokasi_coil' => 'AL']);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN_COIL))
            ->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => ''])
            ->assertRedirect();

        $this->assertNull($record->fresh()->lokasi_coil);
        $this->assertDatabaseHas('coil_tag_location_histories', [
            'coil_tag_id' => $record->id,
            'from_location' => 'AL',
            'to_location' => null,
            'source' => CoilTagLocationHistory::SOURCE_ADMIN_MANUAL,
        ]);
    }

    public function test_admin_can_create_a_new_master_location_by_typing_it(): void
    {
        $record = $this->createCoilTag();

        $this->actingAs($this->makeUser(User::ROLE_ADMIN_COIL))
            ->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => 'zz9'])
            ->assertRedirect();

        $this->assertDatabaseHas('coil_locations', ['kode' => 'ZZ9']);
        $this->assertSame('ZZ9', $record->fresh()->lokasi_coil);

        $this->get('/admin-coil/coil-tags')
            ->assertOk()
            ->assertSee('location=ZZ9');
    }

    public function test_same_location_does_not_create_history_entries(): void
    {
        $record = $this->createCoilTag(['lokasi_coil' => 'AL']);

        $this->actingAs($this->makeUser(User::ROLE_ADMIN_COIL))
            ->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => 'al'])
            ->assertRedirect();

        $this->assertSame('AL', $record->fresh()->lokasi_coil);
        $this->assertDatabaseCount('coil_tag_location_histories', 0);
    }

    public function test_location_import_preview_and_execute_updates_matched_rows_only(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN_COIL);
        $matched = $this->createCoilTag(['nomor_coil' => 'HKG0268301', 'lokasi_coil' => null]);
        $this->createCoilTag(['nomor_coil' => 'HKG0268399', 'tag_taehang' => '09-021', 'lokasi_coil' => 'AL']);

        $this->actingAs($admin);

        $file = $this->uploadXlsx([
            ['Nomor Coil', 'Lokasi'],
            ['HKG0268301', 'br'],
            ['HKF0000000', 'CR'],
        ]);

        $preview = $this->post('/admin-coil/lokasi/import/preview', ['file' => $file]);
        $preview->assertOk();
        $preview->assertSee('HKG0268301');
        $preview->assertSee('HKF0000000');
        $preview->assertSee('1 akan diperbarui');
        $preview->assertSee('1 tidak ditemukan');

        $this->post('/admin-coil/lokasi/import/execute')
            ->assertRedirect(route('admin-coil.coil-tags.index'));

        $this->assertSame('BR', $matched->fresh()->lokasi_coil);
        $this->assertDatabaseHas('coil_tag_location_histories', [
            'coil_tag_id' => $matched->id,
            'from_location' => null,
            'to_location' => 'BR',
            'source' => CoilTagLocationHistory::SOURCE_ADMIN_IMPORT,
            'changed_by' => $admin->id,
        ]);

        // Incremental: coils absent from the file must stay untouched.
        $this->assertDatabaseHas('coil_tags', ['nomor_coil' => 'HKG0268399', 'lokasi_coil' => 'AL']);
        $this->assertDatabaseMissing('coil_tags', ['nomor_coil' => 'HKF0000000']);
    }

    public function test_location_import_requires_nomor_coil_and_lokasi_headers(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN_COIL));

        $file = $this->uploadXlsx([
            ['Nomor Coil', 'Grade'],
            ['HKG0268301', 'SNI'],
        ]);

        $this->from('/admin-coil/lokasi/import')
            ->post('/admin-coil/lokasi/import/preview', ['file' => $file])
            ->assertRedirect('/admin-coil/lokasi/import')
            ->assertSessionHas('error');
    }

    public function test_location_import_execute_without_session_is_rejected(): void
    {
        $this->actingAs($this->makeUser(User::ROLE_ADMIN_COIL))
            ->post('/admin-coil/lokasi/import/execute')
            ->assertRedirect(route('admin-coil.locations.import'))
            ->assertSessionHas('error');
    }

    public function test_movements_page_combines_scan_and_location_events(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN_COIL);
        $record = $this->createCoilTag([
            'nomor_coil' => 'HKG0268301',
            'raw_kp' => 'complete raw KP text',
            'raw_taehang' => 'complete raw Taehang text',
        ]);

        $this->actingAs($admin);
        $this->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => 'AL']);

        $this->get('/admin-coil/pergerakan-coil')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertSee('complete raw KP text')
            ->assertSee('complete raw Taehang text')
            ->assertSee('Pindah Lokasi')
            ->assertSee('Scan')
            ->assertSee('Lokasi');
    }

    public function test_movements_filter_by_type_and_search(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN_COIL);
        $withMovement = $this->createCoilTag(['nomor_coil' => 'HKG0268301', 'tag_taehang' => '09-020']);
        $this->createCoilTag(['nomor_coil' => 'HKF9999999', 'tag_taehang' => '09-021']);

        $this->actingAs($admin);
        $this->post('/admin-coil/coil-tags/'.$withMovement->id.'/lokasi', ['lokasi_coil' => 'AL']);

        $this->get('/admin-coil/pergerakan-coil?type=lokasi')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertDontSee('HKF9999999');

        $this->get('/admin-coil/pergerakan-coil?search=HKF9999999')
            ->assertOk()
            ->assertSee('HKF9999999')
            ->assertDontSee('HKG0268301');
    }

    public function test_location_endpoints_require_admin_coil_role(): void
    {
        $record = $this->createCoilTag();

        foreach (['gudang', 'checker', 'admin'] as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get('/admin-coil/pergerakan-coil')->assertForbidden();
            $this->actingAs($user)->get('/admin-coil/lokasi/import')->assertForbidden();
            $this->actingAs($user)
                ->post('/admin-coil/coil-tags/'.$record->id.'/lokasi', ['lokasi_coil' => 'AL'])
                ->assertForbidden();
        }
    }

    private function uploadXlsx(array $rows): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'coil-loc');
        file_put_contents($temp, $this->makeXlsx($rows));

        return new UploadedFile(
            $temp,
            'lokasi.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function makeXlsx(array $rows): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'coil-fixture');
        $zipPath = $temp.'.xlsx';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sheetData = '';
        foreach ($rows as $rowIndex => $rowValues) {
            $sheetData .= '<row r="'.($rowIndex + 1).'">';
            foreach ($rowValues as $colIndex => $value) {
                $ref = $this->colLetter($colIndex + 1).($rowIndex + 1);
                if (is_numeric($value)) {
                    $sheetData .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } else {
                    $sheetData .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
                }
            }
            $sheetData .= '</row>';
        }

        $sheetXml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetData.'</sheetData></worksheet>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($zipPath);
        unlink($temp);
        unlink($zipPath);

        return $content === false ? '' : $content;
    }

    private function colLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $column--;
            $letter = chr(65 + ($column % 26)).$letter;
            $column = intdiv($column, 26);
        }

        return $letter;
    }
}
