<?php

namespace App\Support;

class SpreadsheetUploadRules
{
    /** @return list<string|\Illuminate\Contracts\Validation\ValidationRule> */
    public static function importFile(): array
    {
        return [
            'required',
            'file',
            'extensions:xlsx,xls,csv',
            'max:51200',
        ];
    }
}
