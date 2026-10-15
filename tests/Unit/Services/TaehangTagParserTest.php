<?php

namespace Tests\Unit\Services;

use App\Services\TaehangTagParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaehangTagParserTest extends TestCase
{
    #[DataProvider('validTags')]
    public function test_valid_tag_is_parsed_and_raw_input_is_preserved(string $rawTag, string $tag): void
    {
        $parsed = (new TaehangTagParser)->parse($rawTag);

        $this->assertSame($tag, $parsed['tag_taehang']);
        $this->assertSame($rawTag, $parsed['raw_taehang']);
    }

    public static function validTags(): array
    {
        return [
            '09 tag' => ['09-020', '09-020'],
            '01 tag' => ['01-001', '01-001'],
            '99 tag' => ['99-999', '99-999'],
            'raw whitespace preserved' => [' 09-020 ', '09-020'],
        ];
    }

    #[DataProvider('invalidTags')]
    public function test_invalid_tag_structure_is_rejected(string $rawTag): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TaehangTagParser)->parse($rawTag);
    }

    public static function invalidTags(): array
    {
        return [
            'one leading digit' => ['9-020'],
            'two trailing digits' => ['09-20'],
            'four trailing digits' => ['09-0200'],
            'wrong separator' => ['09_020'],
            'letters instead of digits' => ['AB-020'],
            'unicode digits are not accepted' => ['٠٩-٠٢٠'],
            'extra payload' => ['09-020-extra'],
            'empty tag' => [''],
        ];
    }
}
