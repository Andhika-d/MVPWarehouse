<?php

namespace Tests\Unit;

use App\Support\XlsxParser;
use Tests\TestCase;

class XlsxParserTest extends TestCase
{
    public function test_invalid_workbook_error_uses_the_active_locale(): void
    {
        $this->app->setLocale('en');
        $path = tempnam(sys_get_temp_dir(), 'invalid-xlsx-');

        try {
            file_put_contents($path, 'not a workbook');

            try {
                XlsxParser::parse($path);
                $this->fail('Expected the invalid workbook to be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Unable to open the Excel file (.xlsx).', $exception->getMessage());
            }
        } finally {
            @unlink($path);
        }
    }
}
