<?php

namespace StarterSolutions\InertiaDataTable\Mixin;

use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator;

/**
 * @method \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator dataTable(string $tableKey, array|string $columns = [], string|null $pageName = null, \Closure|int|null $total = null, \Closure|null $filterUsing = null, array $additional = [], int|null|\Closure $defaultPerPage = null, int|null $defaultPage = null, string|null $defaultSortBy = null, bool|null $defaultDescending = null)
 * 
 * @mixin \Illuminate\Database\Query\Builder
 */
class QueryDataTableMixin
{
    public function dataTable()
    {
        /**
         * Paginate the given query.
         *
         * @param  string  $tableKey
         * @param  string|\Illuminate\Contracts\Database\Query\Expression|array<string|\Illuminate\Contracts\Database\Query\Expression>  $columns
         * @param  string|null  $pageName
         * @param  \Closure|int|null  $total
         * @param  \Closure|null  $filterUsing
         * @param  array  $additional
         * @param  int|null|\Closure  $defaultPerPage
         * @param  int|null  $defaultPage
         * @param  string|null  $defaultSortBy
         * @param  bool|null  $defaultDescending
         * 
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator    
         */
        return function (
            $tableKey, 
            $columns = ['*'], 
            $pageName = null, 
            $total = null, 
            $filterUsing = null,
            $additional = [],
            $defaultPerPage = null,
            $defaultPage = null,
            $defaultSortBy = null,
            $defaultDescending = null,
        ): SortableFilterPaginator {
            /** @var \Illuminate\Database\Query\Builder $this */
            $query  = $this;

            $config = Config::get('inertia-data-table');

            $session = (Request::query($config['table_key_param']) === $tableKey) 
                // if the current request has a matching tableKey, prioritize its query parameters over session values
                ? null
                // otherwise, use session values (if available) to maintain state across requests
                : Request::session()->get("inertia-data-table.{$tableKey}")
            ;

            // apply filtering (if provided)
            $filter = $session['filter'] ?? Request::query($config['filter_param']);
            $filter = is_array($filter) ? $filter : [];

            if ($filterUsing) {
                if (!is_callable($filterUsing)) {
                    throw new \InvalidArgumentException("The filter argument must be a callable (e.g. a closure that accepts the query builder and filter array as parameters).");
                }

                $filterUsing($query, $filter);
            }

            // apply sorting
            $sortBy = $session['sortBy']
                ?? Request::query($config['sort_by_param'])
                ?? $defaultSortBy
                ?? $config['default_sort_by'];
            $descending = $session['descending']
                ?? (Request::has($config['descending_param'])
                    ? Request::boolean($config['descending_param'])
                    : ($defaultDescending ?? $config['default_decending']));
            $direction = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);

            // determine pagination parameters
            $pageName = $pageName ?? $config['page_name_param'];
            $total = value($total) ?? $query->getCountForPagination();
            $perPage = $session['perPage']
                ?? Request::query($config['per_page_param'])
                ?? value($defaultPerPage, $total)
                ?? $config['default_per_page'];
            $all = $perPage <= 0;
            if($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $session['page'] ?? Paginator::resolveCurrentPage($pageName, $defaultPage);
                $query = $query->forPage($page, $perPage);
            }

            $results = $total
                ? $query->get($columns)
                : new Collection;

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
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                    'query'    => Request::query(),
                ]
            );
        };
    }
}
