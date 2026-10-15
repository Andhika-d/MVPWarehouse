<?php

namespace Tests\Unit;

use App\Services\KpScanParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KpScanParserTest extends TestCase
{
    #[DataProvider('validKpScans')]
    public function test_valid_nine_field_scan_is_parsed_and_raw_input_is_preserved(string $rawKp): void
    {
        $parsed = (new KpScanParser)->parse($rawKp);

        $this->assertSame('0', $parsed['field_1']);
        $this->assertSame('HKD0215600008', $parsed['nomor_coil']);
        $this->assertSame('JIS G3101 SS400', $parsed['grade']);
        $this->assertSame('5.7', $parsed['tebal']);
        $this->assertSame('1495', $parsed['lebar']);
        $this->assertSame('5.7 × 1495', $parsed['ukuran']);
        $this->assertSame('0', $parsed['field_6']);
        $this->assertSame('3.609', $parsed['berat']);
        $this->assertSame('PTKP', $parsed['company']);
        $this->assertSame('0', $parsed['field_9']);
        $this->assertSame($rawKp, $parsed['raw_kp']);
    }

    public static function validKpScans(): array
    {
        return [
            'trailing comma' => ['0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0,'],
            'no trailing comma' => ['0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0'],
            'surrounding whitespace remains raw' => [' 0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0, '],
        ];
    }

    #[DataProvider('invalidFieldCounts')]
    public function test_scan_with_other_than_nine_fields_is_rejected(string $rawKp): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new KpScanParser)->parse($rawKp);
    }

    public static function invalidFieldCounts(): array
    {
        return [
            'fewer than nine fields' => ['0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP'],
            'more than nine fields' => ['0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0,extra'],
            'two trailing commas' => ['0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0,,'],
        ];
    }

    #[DataProvider('invalidKpValues')]
    public function test_invalid_or_nonpositive_required_kp_value_is_rejected(int $index, string $value, string $message): void
    {
        $fields = explode(',', '0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0');
        $fields[$index] = $value;

        try {
            (new KpScanParser)->parse(implode(',', $fields));
            $this->fail('The invalid numeric value should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public static function invalidKpValues(): array
    {
        return [
            'non-numeric thickness' => [3, 'thick', 'Tebal harus berupa angka lebih besar dari 0.'],
            'zero thickness' => [3, '0', 'Tebal harus berupa angka lebih besar dari 0.'],
            'negative thickness' => [3, '-1', 'Tebal harus berupa angka lebih besar dari 0.'],
            'non-numeric width' => [4, 'wide', 'Lebar harus berupa angka lebih besar dari 0.'],
            'zero width' => [4, '0', 'Lebar harus berupa angka lebih besar dari 0.'],
            'negative width' => [4, '-1', 'Lebar harus berupa angka lebih besar dari 0.'],
            'non-numeric weight' => [6, 'heavy', 'Berat harus berupa angka lebih besar dari 0.'],
            'zero weight' => [6, '0', 'Berat harus berupa angka lebih besar dari 0.'],
            'negative weight' => [6, '-1', 'Berat harus berupa angka lebih besar dari 0.'],
            'infinite weight' => [6, 'INF', 'Berat harus berupa angka lebih besar dari 0.'],
        ];
    }

    #[DataProvider('invalidRequiredTextFields')]
    public function test_empty_required_coil_or_grade_is_rejected(int $index, string $message): void
    {
        $fields = explode(',', '0,HKD0215600008,JIS G3101 SS400,5.7,1495,0,3.609,PTKP,0');
        $fields[$index] = ' ';

        try {
            (new KpScanParser)->parse(implode(',', $fields));
            $this->fail('The empty required field should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public static function invalidRequiredTextFields(): array
    {
        return [
            'empty coil number' => [1, 'Nomor coil wajib diisi.'],
            'empty grade' => [2, 'Grade wajib diisi.'],
        ];
    }
}
