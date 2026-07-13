<?php

namespace StarterSolutions\InertiaDataTable\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SortableFilterPaginator extends LengthAwarePaginator
{
    protected string $sortBy;
    protected bool   $descending;
    protected int    $rawPerPage;
    protected array  $additional = [];

    /**
     * Create a new sortable paginator instance.
     *
     * @param  array|Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  string  $sortBy
     * @param  bool  $descending
     * @param  bool  $all  Whether to fetch all items (ignoring pagination)
     * @param  array  $additional
     * @param  array  $options
     * @return void
     */
    public function __construct(
        $items,
        $total,
        $perPage,
        $currentPage = null,
        $sortBy,
        $descending = false,
        $all = false,
        $additional = [],
        $options = []
    ) {
        $this->sortBy     = $sortBy;
        $this->descending = $descending;
        $this->rawPerPage = $perPage;
        $this->additional = $additional;

        $perPage = $all ? $total : $perPage;

        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['sort_by']    = $this->sortBy;
        $data['descending'] = $this->descending;
        $data['per_page']   = $this->rawPerPage;

        if ($this->additional !== []) {
            $data['additional'] = $this->additional;
        }

        return $data;
    }

    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional, $data);

        return $this;
    }

    public function getAdditional(): array
    {
        return $this->additional;
    }
}