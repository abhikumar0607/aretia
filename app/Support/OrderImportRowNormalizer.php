<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class OrderImportRowNormalizer
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeKeys(array $data): array
    {
        $dueDate = $data['due_date']
            ?? $data['due_date_yyyy_mm_dd']
            ?? null;

        if ($dueDate !== null) {
            $data['due_date'] = $dueDate;
        }

        unset($data['due_date_yyyy_mm_dd']);

        return $data;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @return list<int>
     */
    public static function pastDueDateRowNumbers(Collection $rows): array
    {
        $today = now()->startOfDay();
        $invalidRows = [];

        foreach ($rows as $index => $row) {
            $data = self::normalizeKeys($row->toArray());

            if (self::isEmptyRow($data)) {
                continue;
            }

            $dueDate = SpreadsheetDateParser::parseOptional($data['due_date'] ?? null);
            if ($dueDate !== null && $dueDate->lt($today)) {
                $invalidRows[] = $index + 2;
            }
        }

        return $invalidRows;
    }

    /**
     * @param  list<int>  $rowNumbers
     */
    public static function pastDueDateMessage(array $rowNumbers): string
    {
        $rows = implode(', ', $rowNumbers);

        return "Import cancelled. Row {$rows} has a due date in the past. Please fix the old date(s) — use today or a future date (YYYY-MM-DD) — and upload the file again.";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
