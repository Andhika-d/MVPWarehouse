<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use ZipArchive;
use Tests\TestCase;

class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test',
            'email' => $role . '-' . uniqid() . '@example.com',
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    private function uploadFile(string $name, string $content, string $mime): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'import');
        file_put_contents($temp, $content);

        return new UploadedFile($temp, $name, $mime, null, true);
    }

    private function makeXlsx(array $rows): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'fixture');
        $zipPath = $temp . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sheetData = '';
        foreach ($rows as $rowIndex => $rowValues) {
            $sheetData .= '<row r="' . ($rowIndex + 1) . '">';
            foreach ($rowValues as $colIndex => $value) {
                $ref = $this->colLetter($colIndex + 1) . ($rowIndex + 1);
                if (is_numeric($value)) {
                    $sheetData .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                } else {
                    $sheetData .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . htmlspecialchars((string) $value, ENT_XML1) . '</t></is></c>';
                }
            }
            $sheetData .= '</row>';
        }

        $sheetXml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetData . '</sheetData></worksheet>';

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
            $modulo = ($column - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $column = (int) floor(($column - $modulo) / 26);
        }

        return $letter;
    }

    public function test_import_creates_items_with_size_and_parsed_unit(): void
    {
        $file = $this->uploadFile('stok.xlsx', $this->makeXlsx([
            ['Nama Barang', 'Size', 'Qty'],
            ['Waterpas Magnet', '44 Cm', '4 pcs'],
            ['Waterpas Magnet', '99 Cm', '2 Roll'],
            ['Karpet', '', '10'],
        ]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items/import', ['file' => $file, 'rack_location' => 'B'])
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHas('success', '3 item berhasil diimpor ke Rak B.');

        $this->assertSame(3, Item::count());

        $waterpas44 = Item::where('name', 'Waterpas Magnet')->where('size', '44 Cm')->first();
        $this->assertNotNull($waterpas44);
        $this->assertSame(4, $waterpas44->stock);
        $this->assertSame('Pcs', $waterpas44->unit);
        $this->assertSame('B', $waterpas44->rack_location);

        $waterpas99 = Item::where('name', 'Waterpas Magnet')->where('size', '99 Cm')->first();
        $this->assertNotNull($waterpas99);
        $this->assertSame(2, $waterpas99->stock);
        $this->assertSame('Roll', $waterpas99->unit);

        $karpet = Item::where('name', 'Karpet')->first();
        $this->assertNotNull($karpet);
        $this->assertNull($karpet->size);
        $this->assertSame(10, $karpet->stock);
        $this->assertSame('Pcs', $karpet->unit);
    }

    public function test_import_skips_rows_with_empty_name(): void
    {
        $file = $this->uploadFile('stok.xlsx', $this->makeXlsx([
            ['Nama Barang', 'Size', 'Qty'],
            ['Lakban Bening', '2 Inch', '4 pcs'],
            ['', 'X', '5'],
        ]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items/import', ['file' => $file, 'rack_location' => 'A'])
            ->assertSessionHas('success', '1 item berhasil diimpor ke Rak A. 1 baris dilewati (nama barang kosong).');

        $this->assertSame(1, Item::count());
    }

    public function test_import_rejects_non_xlsx_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.txt', 'hello');

        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items/import', ['file' => $file, 'rack_location' => 'A'])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Item::count());
    }

    public function test_import_requires_admin_role(): void
    {
        $file = $this->uploadFile('stok.xlsx', $this->makeXlsx([
            ['Nama Barang', 'Size', 'Qty'],
            ['Lakban Bening', '2 Inch', '4 pcs'],
        ]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->makeUser('gudang'))
            ->post('/admin/items/import', ['file' => $file, 'rack_location' => 'A'])
            ->assertForbidden();
    }

    public function test_import_rejects_missing_name_header(): void
    {
        $file = $this->uploadFile('stok.xlsx', $this->makeXlsx([
            ['Size', 'Qty'],
            ['44 Cm', '4 pcs'],
        ]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items/import', ['file' => $file, 'rack_location' => 'A'])
            ->assertSessionHas('error', 'Kolom "Nama Barang" tidak ditemukan di file Excel.');

        $this->assertSame(0, Item::count());
    }

    public function test_store_item_validates_standard_unit_and_size(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items', [
                'name' => 'Obeng',
                'size' => '10 Inch',
                'rack_location' => 'C',
                'stock' => 5,
                'unit' => 'Pcs',
            ])
            ->assertRedirect('/admin/dashboard');

        $obeng = Item::where('name', 'Obeng')->first();
        $this->assertNotNull($obeng);
        $this->assertSame('10 Inch', $obeng->size);

        $this->actingAs($this->makeUser('admin'))
            ->post('/admin/items', [
                'name' => 'Barang Aneh',
                'rack_location' => 'C',
                'stock' => 1,
                'unit' => 'Biji',
            ])
            ->assertSessionHasErrors('unit');
    }

    public function test_request_barang_dropdown_shows_combined_name_and_size(): void
    {
        Item::create(['name' => 'Waterpas Magnet', 'size' => '44 Cm', 'rack_location' => 'A', 'stock' => 4, 'unit' => 'Pcs']);
        Item::create(['name' => 'Karpet', 'size' => null, 'rack_location' => 'A', 'stock' => 10, 'unit' => 'Pcs']);

        $this->actingAs($this->makeUser('gudang'))
            ->get('/gudang/request-barang')
            ->assertOk()
            ->assertSee('Waterpas Magnet (44 Cm)')
            ->assertSee('Karpet');
    }

    public function test_display_name_formats_with_size(): void
    {
        $withSize = Item::create(['name' => 'Waterpas Magnet', 'size' => '88cm', 'rack_location' => 'A', 'stock' => 1, 'unit' => 'Pcs']);
        $withoutSize = Item::create(['name' => 'Karpet', 'size' => null, 'rack_location' => 'A', 'stock' => 1, 'unit' => 'Pcs']);

        $this->assertSame('Waterpas Magnet (88cm)', $withSize->display_name);
        $this->assertSame('Karpet', $withoutSize->display_name);
    }
}
