<?php

namespace Tests\Feature;

use App\Support\SpreadsheetDateParser;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Tests\TestCase;

class SpreadsheetDateParseTest extends TestCase
{
    public function test_parses_excel_serial_date(): void
    {
        $serial = ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-06-20'));

        $parsed = SpreadsheetDateParser::parseOptional($serial);

        $this->assertNotNull($parsed);
        $this->assertSame('2026-06-20', $parsed->format('Y-m-d'));
    }

    public function test_parses_excel_serial_from_string_number(): void
    {
        $serial = (string) ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-07-15'));

        $parsed = SpreadsheetDateParser::parseOptional($serial);

        $this->assertNotNull($parsed);
        $this->assertSame('2026-07-15', $parsed->format('Y-m-d'));
    }

    public function test_parses_iso_and_common_excel_text_formats(): void
    {
        $this->assertSame('2026-06-15', SpreadsheetDateParser::parseOptional('2026-06-15')?->format('Y-m-d'));
        $this->assertSame('2026-06-15', SpreadsheetDateParser::parseOptional('15/06/2026')?->format('Y-m-d'));
        $this->assertSame('2026-06-15', SpreadsheetDateParser::parseOptional('15-06-2026')?->format('Y-m-d'));
    }

    public function test_empty_values_return_null(): void
    {
        $this->assertNull(SpreadsheetDateParser::parseOptional(null));
        $this->assertNull(SpreadsheetDateParser::parseOptional(''));
        $this->assertNull(SpreadsheetDateParser::parseOptional('   '));
    }
}
