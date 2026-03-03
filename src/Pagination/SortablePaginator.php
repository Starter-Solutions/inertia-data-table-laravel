<?php

namespace StarterSolutions\InertiaDataTable\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SortablePaginator extends LengthAwarePaginator
{
    protected string $sortBy;
    protected bool   $descending;
    protected int    $rawPerPage;

    /**
     * Create a new sortable paginator instance.
     *
     * @param  array|Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  string|null  $sortBy
     * @param  bool  $descending
     * @param  bool  $all  Whether to fetch all items (ignoring pagination)
     * @param  array  $options
     * @return void
     */
    public function __construct(
        $items,
        $total,
        $perPage,
        $currentPage = null,
        $sortBy = null,
        $descending = false,
        $all = false,
        $options = []
    ) {
        $this->sortBy     = $sortBy ?? Config::get('inertia-data-table.default_sort_by');
        $this->descending = $descending;
        $this->rawPerPage = $perPage;

        $perPage = $all ? $total : $perPage;

        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }

    public function toArray(): array
    {
        $meta = parent::toArray();
        $meta['sort_by']    = $this->sortBy;
        $meta['descending'] = $this->descending;
        $meta['per_page']   = $this->rawPerPage;
        return $meta;
    }
}