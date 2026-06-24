<?php

namespace App\Imports;

use App\Models\User;
use App\Services\OrderCreationService;
use App\Support\OrderImportRowNormalizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrdersImport implements WithMultipleSheets
{
    /** @var array<int, array{row: int, message: string}> */
    public array $errors = [];

    public int $imported = 0;

    public function __construct(
        private User $actingUser,
        private bool $forAdmin,
        private OrderCreationService $orderService,
        private \App\Services\OrderDocumentService $documentService,
        /** @var list<array{name: string, data: string}> */
        private array $documents = [],
    ) {}

    public function sheets(): array
    {
        return [
            0 => new OrdersSheetImport($this),
        ];
    }

    public function processRows(Collection $rows): void
    {
        $pastDueRows = OrderImportRowNormalizer::pastDueDateRowNumbers($rows);
        if ($pastDueRows !== []) {
            throw new \InvalidArgumentException(
                OrderImportRowNormalizer::pastDueDateMessage($pastDueRows)
            );
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = OrderImportRowNormalizer::normalizeKeys($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            try {
                $order = $this->orderService->createFromRow(
                    $this->sanitizeRow($data),
                    $this->actingUser,
                    $this->forAdmin,
                );

                $this->documentService->attachMany($order, $this->actingUser->id, $this->documents);

                $this->imported++;
            } catch (\Throwable $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeRow(array $data): array
    {
        if ($this->forAdmin) {
            return $data;
        }

        unset($data['company_name'], $data['company_email']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
