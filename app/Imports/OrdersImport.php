<?php

namespace App\Imports;

use App\Models\User;
use App\Services\OrderCreationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrdersImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, message: string}> */
    public array $errors = [];

    public int $imported = 0;

    public function __construct(
        private User $actingUser,
        private bool $forAdmin,
        private OrderCreationService $orderService,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $row->toArray();

            if ($this->isEmptyRow($data)) {
                continue;
            }

            try {
                $this->orderService->createFromRow($data, $this->actingUser, $this->forAdmin);
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
