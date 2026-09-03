<?php

namespace StarterSolutions\InertiaDataTable\Mixin;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator;

/**
 * @method \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator dataTable(string $tableKey, string|null $pageName = null, \Closure|null $filterUsing = null, array $additional = [], int|null|\Closure $defaultPerPage = null, int|null $defaultPage = null, string|null $defaultSortBy = null, bool|null $defaultDescending = null)
 *
 * @mixin \Illuminate\Support\Collection
 */
class CollectionDataTableMixin
{
    public function dataTable()
    {
        /**
         * Paginate the collection for an Inertia data table.
         *
         * The filter callback receives the collection and the filter values. It
         * may either return the filtered items or modify the collection in place.
         *
         * @param  string  $tableKey
         * @param  string|null  $pageName
         * @param  \Closure|null  $filterUsing
         * @param  array  $additional
         * @param  int|null|\Closure  $defaultPerPage
         * @param  int|null  $defaultPage
         * @param  string|null  $defaultSortBy
         * @param  bool|null  $defaultDescending
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator
         *
         * @throws \InvalidArgumentException
         */
        return function (
            $tableKey,
            $pageName = null,
            $filterUsing = null,
            $additional = [],
            $defaultPerPage = null,
            $defaultPage = null,
            $defaultSortBy = null,
            $defaultDescending = null,
        ): SortableFilterPaginator {
            /** @var \Illuminate\Support\Collection $this */
            $items = $this;

            $config = Config::get('inertia-data-table');

            $session = (Request::query($config['table_key_param']) === $tableKey)
                ? null
                : Request::session()->get("inertia-data-table.{$tableKey}");

            $filter = $session['filter'] ?? Request::query($config['filter_param']);
            $filter = is_array($filter) ? $filter : [];

            if ($filterUsing) {
                if (! is_callable($filterUsing)) {
                    throw new \InvalidArgumentException('The filter argument must be a callable (e.g. a closure that accepts the collection and filter array as parameters).');
                }

                $filteredItems = $filterUsing($items, $filter);

                if ($filteredItems !== null) {
                    $items = $filteredItems instanceof Collection
                        ? $filteredItems
                        : new Collection($filteredItems);
                }
            }

            $sortBy = $session['sortBy']
                ?? Request::query($config['sort_by_param'])
                ?? $defaultSortBy
                ?? $config['default_sort_by'];
            $descending = $session['descending']
                ?? (Request::has($config['descending_param'])
                    ? Request::boolean($config['descending_param'])
                    : ($defaultDescending ?? $config['default_decending']));

            $items = ($descending ? $items->sortByDesc($sortBy) : $items->sortBy($sortBy))->values();
            $total = $items->count();

            $pageName = $pageName ?? $config['page_name_param'];
            $perPage = $session['perPage']
                ?? Request::query($config['per_page_param'])
                ?? value($defaultPerPage, $total)
                ?? $config['default_per_page'];
            $all = $perPage <= 0;
            $page = $all
                ? 1
                : ($session['page'] ?? Paginator::resolveCurrentPage($pageName, $defaultPage));
            $results = $all ? $items : $items->forPage($page, $perPage)->values();

            return new SortableFilterPaginator(
                items: $results,
                total: $total,
                perPage: $perPage,
                currentPage: $page,
                sortBy: $sortBy,
                descending: $descending,
                all: $all,
                filter: $filter,
                additional: $additional,
                options: [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                    'query' => Request::query(),
                ]
            );
        };
    }
}
