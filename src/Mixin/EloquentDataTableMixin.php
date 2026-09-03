<?php

namespace StarterSolutions\InertiaDataTable\Mixin;

use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator;

/**
 * @method \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator dataTable(string $tableKey, int|null|\Closure $defaultPerPage = null, array|string  $columns = [], string|null  $pageName = null, int|null  $defaultPage = null, \Closure|int|null  $total = null, string|null  $defaultSortBy = null, bool|null  $defaultDescending = null, \Closure|null  $filterUsing = null, array  $additional = [])
 * 
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class EloquentDataTableMixin
{
    public function dataTable()
    {
        /**
         * Paginate the given eloquent query.
         *
         * @param  string  $tableKey
         * @param  int|null|\Closure  $defaultPerPage
         * @param  array|string  $columns
         * @param  string|null  $pageName
         * @param  int|null  $defaultPage
         * @param  \Closure|int|null  $total
         * @param  string|null  $defaultSortBy
         * @param  bool|null  $defaultDescending
         * @param  \Closure|null  $filterUsing
         * @param  array  $additional
         * 
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator
         *
         * @throws \InvalidArgumentException
         */
        return function (
            $tableKey, 
            $defaultPerPage = null,
            $columns = ['*'], 
            $pageName = null, 
            $defaultPage = null,
            $total = null, 
            $defaultSortBy = null,
            $defaultDescending = null,
            $filterUsing = null,
            $additional = [],
        ): SortableFilterPaginator  {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $query = $this;
            
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
                    : ($defaultDescending ?? false));
            $direction  = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);

            // determine pagination parameters
            $pageName   ??= $config['page_name_param'];
            $total      = value($total) ?? $query->toBase()->getCountForPagination();
            $perPage = $session['perPage']
                ?? Request::query($config['per_page_param'])
                ?? value($defaultPerPage, $total)
                ?? $config['default_per_page'];
            $all        = $perPage <= 0;
            if($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $session['page'] ?? Paginator::resolveCurrentPage($pageName, $defaultPage);
                $query = $query->forPage($page, $perPage);
            }

            $results = $total
                ? $query->get($columns)
                : $query->model->newCollection();

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
