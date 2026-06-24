<?php

namespace App\Exports;

use App\Models\ServicePackage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersTemplateExport implements FromArray, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    private const DATA_ROWS = 500;

    /** @var Collection<int, ServicePackage> */
    private Collection $packages;

    public function __construct(private bool $forAdmin = false)
    {
        $this->packages = ServicePackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function title(): string
    {
        return 'Orders';
    }

    public function headings(): array
    {
        $headings = [
            'package_slug',
            'due_date (YYYY-MM-DD)',
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
        $basic = $this->packageLabel('basic-risk-spectrum');
        $standard = $this->packageLabel('standard-risk-spectrum');
        $custom = $this->packageLabel('custom');

        $sampleDueSoon = now()->addDays(14)->format('Y-m-d');
        $sampleDueLater = now()->addDays(30)->format('Y-m-d');

        if ($this->forAdmin) {
            return [
                [
                    'Acme Corp',
                    $basic,
                    $sampleDueSoon,
                    'individual',
                    'John Doe',
                    'Director of ABC Holdings',
                    '',
                ],
                [
                    'Acme Corp',
                    $custom,
                    $sampleDueLater,
                    'entity',
                    'Offshore Holdings XYZ',
                    'Registration no. AE-98765, BVI',
                    'Full due diligence on offshore entity XYZ',
                ],
            ];
        }

        return [
            [
                $standard,
                $sampleDueSoon,
                'entity',
                'Acme Holdings Ltd',
                'Registration no. 12345, Dubai',
                '',
            ],
            [
                $custom,
                $sampleDueLater,
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
            $this->dueDateColumn().'2:'.$this->dueDateColumn().self::DATA_ROWS => 'yyyy-mm-dd',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->buildReferenceSheet($sheet);
                $this->applyDropdowns($sheet);
                $this->applyDueDateValidation($sheet);
                $this->applyColumnWidths($sheet);

                $workbook = $sheet->getParent();
                $workbook->setActiveSheetIndex(0);
                $sheet->setSelectedCell('A1');
            },
        ];
    }

    private function applyDueDateValidation(Worksheet $sheet): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_DATE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1('=TODAY()');
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(false);
        $validation->setPromptTitle('Due date');
        $validation->setPrompt('Optional. Use YYYY-MM-DD (example: '.now()->format('Y-m-d').'). Must be today or a future date.');
        $validation->setErrorTitle('Past date not allowed');
        $validation->setError('This due date is in the past. Use today or a future date (YYYY-MM-DD).');

        $column = $this->dueDateColumn();
        for ($row = 2; $row <= self::DATA_ROWS; $row++) {
            $sheet->getCell("{$column}{$row}")->setDataValidation(clone $validation);
        }
    }

    private function buildReferenceSheet(Worksheet $ordersSheet): void
    {
        $workbook = $ordersSheet->getParent();
        $reference = new Worksheet($workbook, 'Reference');
        $workbook->addSheet($reference, 1);

        $reference->fromArray([
            ['slug', 'name', 'package_picker', '', 'subject_type'],
        ], null, 'A1');

        foreach ($this->packages->values() as $index => $package) {
            $row = $index + 2;
            $reference->setCellValue("A{$row}", $package->slug);
            $reference->setCellValue("B{$row}", $package->name);
            $reference->setCellValue("C{$row}", $this->packageLabel($package));
        }

        $reference->fromArray([
            ['individual'],
            ['entity'],
        ], null, 'E2');

        $reference->getStyle('A1:E1')->getFont()->setBold(true);
        $reference->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    private function applyDropdowns(Worksheet $sheet): void
    {
        if ($this->packages->isNotEmpty()) {
            $lastPackageRow = $this->packages->count() + 1;
            $packageFormula = sprintf("Reference!\$C\$2:\$C\$%d", $lastPackageRow);

            $this->applyListValidation(
                $sheet,
                $this->packageSlugColumn().'2:'.$this->packageSlugColumn().self::DATA_ROWS,
                $packageFormula,
                'Package',
                'Pick a service package from the dropdown.',
                'Choose a package from the list (or type the slug exactly).',
            );
        }

        $this->applyListValidation(
            $sheet,
            $this->subjectTypeColumn().'2:'.$this->subjectTypeColumn().self::DATA_ROWS,
            'Reference!$E$2:$E$3',
            'Subject type',
            'Individual or entity — required for standard packages.',
            'Choose individual or entity from the list.',
        );
    }

    private function applyListValidation(
        Worksheet $sheet,
        string $cellRange,
        string $listFormula,
        string $promptTitle,
        string $prompt,
        string $error,
    ): void {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($listFormula);
        $validation->setPromptTitle($promptTitle);
        $validation->setPrompt($prompt);
        $validation->setErrorTitle('Invalid value');
        $validation->setError($error);

        [$start, $end] = array_pad(explode(':', $cellRange), 2, null);
        $start = $start ?? $cellRange;
        $end = $end ?? $start;

        preg_match('/([A-Z]+)(\d+)/', $start, $startParts);
        preg_match('/([A-Z]+)(\d+)/', $end, $endParts);

        $column = $startParts[1];
        $firstRow = (int) $startParts[2];
        $lastRow = (int) ($endParts[2] ?? $firstRow);

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $sheet->getCell("{$column}{$row}")->setDataValidation(clone $validation);
        }
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        $widths = $this->forAdmin
            ? ['A' => 22, 'B' => 34, 'C' => 22, 'D' => 14, 'E' => 24, 'F' => 28, 'G' => 32]
            : ['A' => 34, 'B' => 22, 'C' => 14, 'D' => 24, 'E' => 28, 'F' => 32];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->freezePane('A2');
    }

    private function packageSlugColumn(): string
    {
        return $this->forAdmin ? 'B' : 'A';
    }

    private function subjectTypeColumn(): string
    {
        return $this->forAdmin ? 'D' : 'C';
    }

    private function dueDateColumn(): string
    {
        return $this->forAdmin ? 'C' : 'B';
    }

    private function packageLabel(string|ServicePackage $package): string
    {
        if (is_string($package)) {
            $slug = $package;
            $package = $this->packages->firstWhere('slug', $slug)
                ?? ServicePackage::query()->where('slug', $slug)->first();

            if (! $package) {
                return $slug;
            }
        }

        return $package->slug.' — '.$package->name;
    }
}
