<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CaseListSorting
{
    public const DIR_ASC = 'asc';

    public const DIR_DESC = 'desc';

    /** @return array<string, string> */
    public static function columns(): array
    {
        return [
            'reference' => 'Reference',
            'subject' => 'Subject',
            'company' => 'Company',
            'package' => 'Package',
            'confirmed' => 'Confirmed',
            'due_date' => 'Due date',
            'stage' => 'Stage',
        ];
    }

    public static function apply(Builder $query, Request $request): void
    {
        $sort = (string) $request->input('sort', '');
        $dir = self::normalizeDir($request->input('dir'));

        if ($sort === '' || ! array_key_exists($sort, self::columns())) {
            $query->latest('cases.created_at');

            return;
        }

        match ($sort) {
            'reference' => $query->orderBy('cases.reference', $dir),
            'company' => $query->orderBy(
                Company::query()->select('name')->whereColumn('companies.id', 'cases.company_id')->limit(1),
                $dir
            ),
            'subject' => $query->orderBy(
                Order::query()->select('subject_name')->whereColumn('orders.id', 'cases.order_id')->limit(1),
                $dir
            ),
            'package' => $query->orderBy(
                ServicePackage::query()
                    ->select('service_packages.name')
                    ->join('orders', 'orders.service_package_id', '=', 'service_packages.id')
                    ->whereColumn('orders.id', 'cases.order_id')
                    ->limit(1),
                $dir
            ),
            'confirmed' => $query->orderBy(
                Order::query()->select('confirmed_at')->whereColumn('orders.id', 'cases.order_id')->limit(1),
                $dir
            ),
            'due_date' => $query->orderBy(
                Order::query()->select('due_date')->whereColumn('orders.id', 'cases.order_id')->limit(1),
                $dir
            ),
            'stage' => $query->orderBy(
                WorkflowStage::query()->select('sort_order')->whereColumn('workflow_stages.id', 'cases.workflow_stage_id')->limit(1),
                $dir
            ),
            default => $query->latest('cases.created_at'),
        };
    }

    public static function sortUrl(string $column, ?Request $request = null): string
    {
        $request ??= request();
        $currentSort = (string) $request->input('sort', '');
        $currentDir = self::normalizeDir($request->input('dir'));

        $nextDir = ($currentSort === $column && $currentDir === self::DIR_ASC)
            ? self::DIR_DESC
            : self::DIR_ASC;

        return $request->fullUrlWithQuery(array_merge(
            $request->query(),
            ['sort' => $column, 'dir' => $nextDir, 'page' => null]
        ));
    }

    public static function isActive(string $column, ?Request $request = null): bool
    {
        return (string) ($request ?? request())->input('sort', '') === $column;
    }

    public static function activeDir(?Request $request = null): string
    {
        return self::normalizeDir(($request ?? request())->input('dir'));
    }

    private static function normalizeDir(mixed $dir): string
    {
        return strtolower((string) $dir) === self::DIR_ASC ? self::DIR_ASC : self::DIR_DESC;
    }
}
