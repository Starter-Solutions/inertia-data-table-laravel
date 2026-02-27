<?php

namespace StarterSolutions\InertiaDataTable\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * @param  array  $options
     * @param  string|null  $sortBy
     * @param  bool  $descending
     * @param  bool  $all  Whether to fetch all items (ignoring pagination)
     * @return void
     */
    public function __construct(
        $items,
        $total,
        $perPage,
        $currentPage = null,
        $options = [],
        $sortBy = null,
        $descending = false,
        $all = false
    ) {
        $this->sortBy     = $sortBy ?? 'id';
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