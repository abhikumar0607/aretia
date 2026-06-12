<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SpreadsheetDateParser
{
    /**
     * Parse a due-date cell from Excel/CSV import or form input.
     */
    public static function parseOptional(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_int($value) || is_float($value)) {
            return self::fromExcelSerial((float) $value);
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && self::looksLikeExcelSerial((float) $value)) {
            return self::fromExcelSerial((float) $value);
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd.m.Y'] as $format) {
            $parsed = self::tryFormat($format, $value);
            if ($parsed !== null) {
                return $parsed->startOfDay();
            }
        }

        return Carbon::parse($value)->startOfDay();
    }

    private static function fromExcelSerial(float $serial): Carbon
    {
        return Carbon::instance(ExcelDate::excelToDateTimeObject($serial))->startOfDay();
    }

    private static function looksLikeExcelSerial(float $value): bool
    {
        // Excel day serials for real calendar dates (roughly 1980–2100).
        return $value >= 20000 && $value <= 80000;
    }

    private static function tryFormat(string $format, string $value): ?Carbon
    {
        if (! preg_match(self::patternForFormat($format), $value)) {
            return null;
        }

        $parsed = Carbon::createFromFormat('!'.$format, $value);

        return $parsed === false ? null : $parsed;
    }

    private static function patternForFormat(string $format): string
    {
        return match ($format) {
            'Y-m-d' => '/^\d{4}-\d{2}-\d{2}$/',
            'd/m/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/',
            'd-m-Y' => '/^\d{1,2}-\d{1,2}-\d{4}$/',
            'm/d/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/',
            'd.m.Y' => '/^\d{1,2}\.\d{1,2}\.\d{4}$/',
            default => '/.*/',
        };
    }
}
