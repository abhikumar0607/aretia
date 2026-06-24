<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrdersSheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(private OrdersImport $import) {}

    public function collection(Collection $rows): void
    {
        $this->import->processRows($rows);
    }
}
