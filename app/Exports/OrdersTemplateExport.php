<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersTemplateExport implements FromArray, WithHeadings, WithStyles
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
            array_unshift($headings, 'company_email');
        }

        return $headings;
    }

    public function array(): array
    {
        if ($this->forAdmin) {
            return [
                [
                    'client@aretia.test',
                    'basic-risk-spectrum',
                    '2026-06-15',
                    'individual',
                    'John Doe',
                    'Director of ABC Holdings',
                    '',
                ],
                [
                    'client@aretia.test',
                    'custom',
                    '',
                    '',
                    '',
                    '',
                    'Full due diligence on offshore entity XYZ',
                ],
            ];
        }

        return [
            [
                'standard-risk-spectrum',
                '2026-06-20',
                'entity',
                'Acme Holdings Ltd',
                'Registration no. 12345, Dubai',
                '',
            ],
            [
                'custom',
                '',
                '',
                '',
                '',
                'Investigate partnership structure for Project Alpha',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
