<?php

namespace Tests\Feature;

use App\Models\CoilTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AdminCoilTagOutputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN_COIL]));
    }

    public function test_admin_page_lists_saved_records_and_filter_controls(): void
    {
        $record = $this->createCoilTag(['lokasi_coil' => 'AL']);

        $this->get('/admin-coil/coil-tags')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertSee('SNI 8784:2024 B')
            ->assertSee('11.730')
            ->assertSee('Total Net WT/Berat Bersih')
            ->assertSee('Net WT/Berat Bersih')
            ->assertSee('Total Qty')
            ->assertSee('Scan Hari Ini')
            ->assertSee('09-020')
            ->assertSee('name="search"', false)
            ->assertSee('name="location"', false)
            ->assertSee('location=AL')
            ->assertSee('location=CR')
            ->assertSee('id="coilTagFilters"', false)
            ->assertSee('name="period_mode"', false)
            ->assertSee('Pilih Periode')
            ->assertSee('id="periodFilterModal"', false)
            ->assertSee('href="'.route('admin-coil.coil-tags.print').'"', false)
            ->assertSee('href="'.route('admin-coil.coil-tags.export').'"', false)
            ->assertSee('href="'.route('admin-coil.coil-tags.show', $record).'"', false);
    }

    public function test_location_filter_limits_data_and_print_and_shows_selected_location(): void
    {
        $this->createCoilTag([
            'nomor_coil' => 'HKG0268301',
            'tag_taehang' => '09-020',
            'lokasi_coil' => 'AL',
        ]);
        $this->createCoilTag([
            'nomor_coil' => 'HKF9999999',
            'tag_taehang' => '09-021',
            'lokasi_coil' => 'BR',
        ]);

        $this->get('/admin-coil/coil-tags?location=AL')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertDontSee('HKF9999999');

        $this->get('/admin-coil/coil-tags/print?location=AL&orientation=portrait')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertDontSee('HKF9999999')
            ->assertSee('A4 portrait', false)
            ->assertSee('AL');

        $this->assertDatabaseHas('coil_tags', ['nomor_coil' => 'HKF9999999', 'lokasi_coil' => 'BR']);
    }

    public function test_search_matches_coil_tag_grade_and_location_in_database(): void
    {
        $this->createCoilTag();
        $this->createCoilTag([
            'nomor_coil' => 'HKF9999999',
            'tag_taehang' => '09-021',
            'grade' => 'SS400',
            'lokasi_coil' => 'RACK-B',
        ]);

        foreach ([
            'HKG0268301' => ['HKG0268301', 'HKF9999999'],
            '09-020' => ['HKG0268301', 'HKF9999999'],
            '8784:2024' => ['HKG0268301', 'HKF9999999'],
            'RACK-B' => ['HKF9999999', 'HKG0268301'],
        ] as $search => [$expected, $excluded]) {
            $this->get('/admin-coil/coil-tags?search='.urlencode($search))
                ->assertOk()
                ->assertSee($expected)
                ->assertDontSee($excluded);
        }
    }

    public function test_period_filter_limits_the_database_results(): void
    {
        $today = $this->createCoilTag(['scanned_at' => now()->startOfDay()->addHours(2)]);
        $this->createCoilTag([
            'nomor_coil' => 'HKF9999999',
            'tag_taehang' => '09-021',
            'scanned_at' => now()->subDays(4),
        ]);

        $this->get('/admin-coil/coil-tags?period_mode=weekly&period_start='.now()->toDateString())
            ->assertOk()
            ->assertSee($today->nomor_coil)
            ->assertDontSee('HKF9999999');

        $this->get('/admin-coil/coil-tags?period_mode=flexible&period_start='.now()->toDateString().'&period_end='.now()->toDateString())
            ->assertOk()
            ->assertSee($today->nomor_coil)
            ->assertDontSee('HKF9999999');
    }

    public function test_excel_export_contains_expected_columns_actual_data_and_active_filters(): void
    {
        $this->createCoilTag([
            'raw_kp' => '0,HKG0268301,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,',
            'raw_taehang' => ' 09-020 ',
            'lokasi_coil' => 'AL',
        ]);
        $this->createCoilTag([
            'nomor_coil' => 'HKF9999999',
            'tag_taehang' => '09-021',
            'grade' => 'OTHER GRADE',
        ]);
        $this->createCoilTag([
            'nomor_coil' => 'HKG0268399',
            'tag_taehang' => '08-020',
            'scanned_at' => now()->subDays(3),
        ]);

        $today = now()->toDateString();
        $response = $this->get('/admin-coil/coil-tags/export?'.http_build_query([
            'search' => 'HKG0268301',
            'period_mode' => 'flexible',
            'period_start' => $today,
            'period_end' => $today,
            'location' => 'AL',
        ]));

        $response->assertOk();
        $response->assertDownload('laporan-coil-tag.xlsx');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'coil-tags-test-');
        file_put_contents($temporaryFile, $response->getContent());
        $zip = new ZipArchive;
        $this->assertSame(true, $zip->open($temporaryFile));
        $worksheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $workbook = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        unlink($temporaryFile);

        $this->assertIsString($worksheet);
        foreach ([
            'No.', 'Company', 'Tag Taehang', 'Nomor Coil', 'Spec', 'Tebal', 'Lebar',
            'Net WT/Berat Bersih', 'Qty.', 'Lokasi', 'Scanned At',
            'HKG0268301', 'SNI 8784:2024 B', '11.730', 'AL',
        ] as $value) {
            $this->assertStringContainsString(htmlspecialchars($value, ENT_XML1), $worksheet);
        }

        $this->assertStringNotContainsString('HKF9999999', $worksheet);
        $this->assertStringNotContainsString('HKG0268399', $worksheet);
        $this->assertStringContainsString('sheet name="Data"', $workbook);
    }

    public function test_print_view_shows_filtered_data_and_print_setup(): void
    {
        $this->createCoilTag();
        $this->createCoilTag([
            'nomor_coil' => 'HKG0268302',
            'tag_taehang' => '09-022',
            'berat' => '12.030',
            'raw_kp' => '0,HKG0268302,SNI 8784:2024 B,1.9,1195,0,12.030,PTKP,0,',
        ]);
        $this->createCoilTag([
            'nomor_coil' => 'HKF9999999',
            'tag_taehang' => '09-021',
        ]);

        $response = $this->get('/admin-coil/coil-tags/print?search=HKG026830')
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertSee('HKG0268302')
            ->assertDontSee('HKF9999999')
            ->assertSee('Berat Bersih')
            ->assertSee('23.760')
            ->assertSee('11.730')
            ->assertSee('@page', false)
            ->assertSee('A4 landscape', false)
            ->assertSee('window.print()', false)
            ->assertSee('approval-grid', false)
            ->assertSee('LAPORAN TAEHANG / COIL TAGGING');

        $html = $response->getContent();
        $columnOrder = ['No.', 'Company', 'Tag Taehang', 'Nomor Coil', 'Spec', 'Tebal', 'Lebar', 'Net WT/Berat Bersih', 'Qty.', 'Lokasi', 'Scanned At'];
        $lastPosition = -1;

        foreach ($columnOrder as $column) {
            $position = strpos($html, '<th>'.$column.'</th>');
            $this->assertNotFalse($position, 'Missing print column: '.$column);
            $this->assertGreaterThan($lastPosition, $position, 'Print column is out of order: '.$column);
            $lastPosition = $position;
        }

        $this->assertStringNotContainsString('<th>Ukuran</th>', $html);
    }

    public function test_pdf_export_downloads_a_pdf_document(): void
    {
        $this->createCoilTag();

        $response = $this->get('/admin-coil/coil-tags/pdf')
            ->assertOk()
            ->assertDownload('laporan-coil-tag.pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_detail_view_shows_all_saved_fields(): void
    {
        $record = $this->createCoilTag([
            'field_1' => 'FIELD ONE',
            'field_6' => 'FIELD SIX',
            'field_9' => 'FIELD NINE',
            'raw_kp' => 'complete raw KP text',
            'raw_taehang' => 'complete raw Taehang text',
            'lokasi_coil' => 'LINE-4',
        ]);

        $this->get('/admin-coil/coil-tags/'.$record->id)
            ->assertOk()
            ->assertSee('HKG0268301')
            ->assertSee('SNI 8784:2024 B')
            ->assertSee('1.9 × 1195')
            ->assertSee('11.730')
            ->assertSee('FIELD ONE')
            ->assertSee('FIELD SIX')
            ->assertSee('FIELD NINE')
            ->assertSee('complete raw KP text')
            ->assertSee('complete raw Taehang text')
            ->assertSee('LINE-4')
            ->assertSee('Created At')
            ->assertSee('Updated At');
    }

    public function test_only_admin_coil_role_may_access_the_output_module(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gudang']))
            ->get('/admin-coil/coil-tags')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'checker']))
            ->get('/admin-coil/coil-tags')->assertForbidden();
    }

    public function test_output_module_renders_in_english_when_user_locale_is_english(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN_COIL, 'locale' => 'en']));
        $this->createCoilTag();

        $this->get('/admin-coil/coil-tags')
            ->assertOk()
            ->assertSee('Coil Number')
            ->assertSee('Search')
            ->assertSee('Scanned At')
            ->assertSee('Export Excel');

        $this->get('/admin-coil/coil-tags/print')
            ->assertOk()
            ->assertSee('Net WT')
            ->assertSee('All Periods')
            ->assertDontSee('Semua Periode');
    }

    private function createCoilTag(array $attributes = []): CoilTag
    {
        return CoilTag::factory()->create(array_replace([
            'nomor_coil' => 'HKG0268301',
            'tag_taehang' => '09-020',
        ], $attributes));
    }
}
