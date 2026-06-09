<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class CollectionPaginator
{
    /**
     * @param  Collection<int, mixed>  $items
     */
    public static function paginate(
        Collection $items,
        ?int $perPage = null,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $perPage = $perPage ?? (int) config('portal.documents_per_page', 5);
        $page = Paginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginatorImpl(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        ))->withQueryString();
    }
}
