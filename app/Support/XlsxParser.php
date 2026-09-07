<?php

namespace App\Support;

use ZipArchive;

class XlsxParser
{
    private const UNIT_MAP = [
        'pcs' => 'Pcs',
        'pck' => 'Pck',
        'pack' => 'Pck',
        'packs' => 'Pck',
        'box' => 'Box',
        'kg' => 'Kg',
        'roll' => 'Roll',
        'rim' => 'Rim',
        'set' => 'Set',
        'sets' => 'Set',
    ];

    /**
     * Read the first worksheet of an .xlsx file into rows of cells.
     *
     * @return array<int, array<int, string>> indexed by row (0-based) => column (0-based)
     */
    public static function parse(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Tidak dapat membuka file Excel (.xlsx).');
        }

        $sharedStrings = self::readSharedStrings($zip);
        $sheetTarget = self::firstSheetTarget($zip);

        if ($sheetTarget === null) {
            $zip->close();

            return [];
        }

        $xml = $zip->getFromName('xl/' . $sheetTarget);
        $zip->close();

        if ($xml === false) {
            return [];
        }

        return self::parseSheet($xml, $sharedStrings);
    }

    /**
     * Parse a qty cell value like "4 pcs", "2 Roll", "4kg", "10".
     *
     * @return array{0: int, 1: string} [quantity, canonical unit]
     */
    public static function parseQuantity(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [0, 'Pcs'];
        }

        if (is_numeric($value)) {
            return [(int) round((float) $value), 'Pcs'];
        }

        if (preg_match('/^([\d.,]+)\s*([a-zA-Z]+)/', $value, $m)) {
            $qty = (int) round(self::normalizeNumber($m[1]));
            $unit = self::UNIT_MAP[strtolower(trim($m[2]))] ?? 'Pcs';

            return [$qty, $unit];
        }

        if (preg_match('/^([\d.,]+)/', $value, $m)) {
            return [(int) round(self::normalizeNumber($m[1])), 'Pcs'];
        }

        return [0, 'Pcs'];
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = self::loadXml($xml);
        if ($doc === null) {
            return [];
        }

        $strings = [];
        foreach ($doc->getElementsByTagNameNS('*', 'si') as $si) {
            $text = '';
            foreach ($si->getElementsByTagNameNS('*', 't') as $t) {
                $text .= $t->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function firstSheetTarget(ZipArchive $zip): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook === false) {
            return null;
        }

        $doc = self::loadXml($workbook);
        if ($doc === null) {
            return null;
        }

        $xpath = new \DOMXPath($doc);
        $sheetNodes = $xpath->query('//*[local-name()="sheet"]');
        if ($sheetNodes === false || $sheetNodes->length === 0) {
            return null;
        }

        $rid = $sheetNodes->item(0)->getAttribute('r:id');
        if ($rid === '') {
            return null;
        }

        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels === false) {
            return null;
        }

        $relDoc = self::loadXml($rels);
        if ($relDoc === null) {
            return null;
        }

        $relXpath = new \DOMXPath($relDoc);
        $relNodes = $relXpath->query('//*[local-name()="Relationship"][@Id="' . $rid . '"]');
        if ($relNodes === false || $relNodes->length === 0) {
            return null;
        }

        return $relNodes->item(0)->getAttribute('Target');
    }

    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $doc = self::loadXml($xml);
        if ($doc === null) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $rowNodes = $xpath->query('//*[local-name()="row"]');
        if ($rowNodes === false) {
            return [];
        }

        $rows = [];
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $cellNodes = $xpath->query('./*[local-name()="c"]', $rowNode);
            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cell) {
                $colIndex = self::columnIndexFromRef($cell->getAttribute('r'));
                $type = $cell->getAttribute('t');
                $value = '';

                $vNode = $cell->getElementsByTagNameNS('*', 'v')->item(0);

                if ($type === 's' && $vNode !== null) {
                    $index = (int) $vNode->textContent;
                    $value = $sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    $is = $cell->getElementsByTagNameNS('*', 'is')->item(0);
                    if ($is !== null) {
                        foreach ($is->getElementsByTagNameNS('*', 't') as $t) {
                            $value .= $t->textContent;
                        }
                    }
                } elseif ($vNode !== null) {
                    $value = $vNode->textContent;
                }

                $cells[$colIndex] = trim($value);
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    private static function columnIndexFromRef(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $letters = $m[1] ?? 'A';

        $col = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $col = $col * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $col - 1);
    }

    private static function normalizeNumber(string $value): float
    {
        $value = trim($value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    private static function loadXml(string $xml): ?\DOMDocument
    {
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $doc : null;
    }
}
