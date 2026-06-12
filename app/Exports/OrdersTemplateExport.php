<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersTemplateExport implements FromArray, WithColumnFormatting, WithHeadings, WithStyles
{
    public function __construct(private bool $forAdmin = false) {}

    public function headings(): array
    {
        $headings = [
            'package_slug',
            'due_date',
            'subject_type',
            'subject_name',
            'subject_details',
            'custom_request',
        ];

        if ($this->forAdmin) {
            array_unshift($headings, 'company_name');
        }

        return $headings;
    }

    public function array(): array
    {
        if ($this->forAdmin) {
            return [
                [
                    'Acme Corp',
                    'basic-risk-spectrum',
                    $this->excelDate('2026-06-15'),
                    'individual',
                    'John Doe',
                    'Director of ABC Holdings',
                    '',
                ],
                [
                    'Acme Corp',
                    'custom',
                    $this->excelDate('2026-07-01'),
                    'entity',
                    'Offshore Holdings XYZ',
                    'Registration no. AE-98765, BVI',
                    'Full due diligence on offshore entity XYZ',
                ],
            ];
        }

        return [
            [
                'standard-risk-spectrum',
                $this->excelDate('2026-06-20'),
                'entity',
                'Acme Holdings Ltd',
                'Registration no. 12345, Dubai',
                '',
            ],
            [
                'custom',
                $this->excelDate('2026-07-15'),
                'entity',
                'Project Alpha Partners',
                'Multi-jurisdiction partnership structure',
                'Investigate partnership structure for Project Alpha',
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            $this->forAdmin ? 'C' : 'B' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function excelDate(string $isoDate): float
    {
        return ExcelDate::PHPToExcel(new \DateTimeImmutable($isoDate));
    }
}
