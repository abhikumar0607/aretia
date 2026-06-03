<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CompanyFilter
{
    /** @return array<int, string> */
    public static function options(): array
    {
        return self::dedupeCompanies(
            Company::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])
        );
    }

    /** @return array<int, string> */
    public static function optionsForUser(?User $user = null): array
    {
        $user ??= auth()->user();

        if ($user?->company_id) {
            $ids = self::equivalentCompanyIds((int) $user->company_id);
            $options = array_intersect_key(self::options(), array_flip($ids));

            if ($options !== []) {
                return $options;
            }

            $company = Company::query()->find($user->company_id);

            return $company ? [$company->id => $company->name] : [];
        }

        return self::options();
    }

    /** @return list<int> */
    public static function scopedCompanyIdsForUser(?User $user = null): array
    {
        $user ??= auth()->user();

        if ($user?->company_id) {
            return self::equivalentCompanyIds((int) $user->company_id);
        }

        return [];
    }

    public static function apply(Builder $query, Request $request, string $column = 'company_id'): void
    {
        $company = $request->input('company');
        if ($company === null || $company === '') {
            return;
        }

        $ids = self::equivalentCompanyIds((int) $company);

        if (count($ids) === 1) {
            $query->where($column, $ids[0]);
        } else {
            $query->whereIn($column, $ids);
        }
    }

    /** @return list<int> */
    public static function equivalentCompanyIds(int $companyId): array
    {
        $company = Company::query()->find($companyId);
        if (! $company) {
            return [$companyId];
        }

        $normalized = self::normalizeName($company->name);

        return Company::query()
            ->get(['id', 'name'])
            ->filter(fn (Company $row) => self::normalizeName($row->name) === $normalized)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array<int, string>
     */
    private static function dedupeCompanies(Collection $companies): array
    {
        $options = [];
        $seen = [];

        foreach ($companies as $company) {
            $key = self::normalizeName($company->name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $options[(int) $company->id] = $company->name;
        }

        uasort($options, fn (string $a, string $b) => strcasecmp($a, $b));

        return $options;
    }
}
