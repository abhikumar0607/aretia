<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class OrderDuplicateSubjects
{
    public static function normalize(?string $subject): ?string
    {
        $subject = trim((string) $subject);

        return $subject === '' ? null : mb_strtolower($subject);
    }

    /**
     * @param  LengthAwarePaginator<int, Order>|EloquentCollection<int, Order>|Collection<int, Order>  $orders
     * @param  list<int>|null  $companyIds
     */
    public static function markOnCollection(
        LengthAwarePaginator|EloquentCollection|Collection $orders,
        ?array $companyIds = null,
    ): void {
        $items = $orders instanceof LengthAwarePaginator ? $orders->getCollection() : $orders;

        if ($items->isEmpty()) {
            return;
        }

        $pageKeys = $items
            ->map(fn (Order $order) => self::normalize($order->subject_name))
            ->filter()
            ->unique()
            ->values();

        if ($pageKeys->isEmpty()) {
            foreach ($items as $order) {
                $order->setAttribute('has_duplicate_subject', false);
            }

            return;
        }

        $duplicateKeys = self::duplicateKeysFor($pageKeys->all(), $companyIds);

        foreach ($items as $order) {
            $key = self::normalize($order->subject_name);
            $order->setAttribute('has_duplicate_subject', $key !== null && isset($duplicateKeys[$key]));
        }
    }

    /**
     * @param  list<string>  $normalizedSubjects
     * @param  list<int>|null  $companyIds
     * @return array<string, true>
     */
    private static function duplicateKeysFor(array $normalizedSubjects, ?array $companyIds): array
    {
        if ($normalizedSubjects === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($normalizedSubjects), '?'));

        $rows = Order::query()
            ->when($companyIds !== null, fn ($query) => $query->whereIn('company_id', $companyIds))
            ->whereNotNull('subject_name')
            ->where('subject_name', '!=', '')
            ->whereRaw('LOWER(TRIM(subject_name)) IN ('.$placeholders.')', $normalizedSubjects)
            ->selectRaw('LOWER(TRIM(subject_name)) as subject_key, COUNT(*) as total')
            ->groupBy('subject_key')
            ->having('total', '>', 1)
            ->pluck('total', 'subject_key');

        $map = [];
        foreach ($rows as $key => $count) {
            $map[(string) $key] = true;
        }

        return $map;
    }
}
