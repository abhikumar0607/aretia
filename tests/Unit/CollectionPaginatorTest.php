<?php

namespace Tests\Unit;

use App\Support\CollectionPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CollectionPaginatorTest extends TestCase
{
    public function test_it_paginates_collections_with_configured_page_size(): void
    {
        config(['portal.documents_per_page' => 5]);

        $items = Collection::range(1, 12);

        $paginator = CollectionPaginator::paginate($items, pageName: 'docs_page');

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(12, $paginator->total());
        $this->assertCount(5, $paginator->items());
        $this->assertSame(3, $paginator->lastPage());
    }
}
