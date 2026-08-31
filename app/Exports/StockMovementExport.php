<?php

namespace App\Exports;

class StockMovementExport
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function toXlsx(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'mov-export');
        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create temporary file for Excel export.');
        }

        $zip = new \ZipArchive();
        $zipPath = $tempFile . '.xlsx';
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create Excel archive.');
        }

        $sheetRows = [];
        $sheetRows[] = ['Tanggal', 'Barang', 'Tipe', 'Jumlah', 'Satuan', 'Stok Sebelum', 'Stok Setelah', 'Keterangan', 'User'];
        foreach ($this->rows as $row) {
            $sheetRows[] = $row;
        }

        $sheetData = '';
        foreach ($sheetRows as $rowIndex => $rowValues) {
            $sheetData .= '<row r="' . ($rowIndex + 1) . '">';
            foreach ($rowValues as $colIndex => $value) {
                $cellRef = $this->getColumnLetter($colIndex + 1) . ($rowIndex + 1);
                if (is_numeric($value)) {
                    $sheetData .= '<c r="' . $cellRef . '"><v>' . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . '</v></c>';
                } else {
                    $sheetData .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . '</t></is></c>';
                }
            }
            $sheetData .= '</row>';
        }

        $sheetXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheetData>
    {{rows}}
  </sheetData>
</worksheet>
XML;

        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Data" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', str_replace('{{rows}}', $sheetData, $sheetXml));
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="1"><border/></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML);
        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML);
        $zip->addFromString('docProps/app.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
  xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Laravel Export</Application>
</Properties>
XML);
        $zip->addFromString('docProps/core.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Perubahan Stok</dc:title>
  <dc:creator>Laravel</dc:creator>
</cp:coreProperties>
XML);

        $zip->close();
        $content = file_get_contents($zipPath);
        unlink($tempFile);
        unlink($zipPath);

        return $content === false ? '' : $content;
    }

    protected function getColumnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $modulo = ($column - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $column = (int) floor(($column - $modulo) / 26);
        }

        return $letter;
    }
}
