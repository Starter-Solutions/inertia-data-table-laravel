<?php

namespace StarterSolutions\InertiaDataTable\Mixin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator;

/**
 * @method \StarterSolutions\InertiaDataTable\Pagination\SortableFilterPaginator dataTable(string $tableKey, array|string $columns = [], string|null $pageName = null, \Closure|int|null $total = null, \Closure|null $filterUsing = null, array $additional = [], int|null|\Closure $defaultPerPage = null, int|null $defaultPage = null, string|null $defaultSortBy = null, bool|null $defaultDescending = null, array|null $allowedSorts = null)
 *
 * @mixin Builder
 */
class EloquentDataTableMixin
{
    public function dataTable()
    {
        /**
         * Paginate the given eloquent query.
         *
         * @param  string  $tableKey
         * @param  array|string  $columns
         * @param  string|null  $pageName
         * @param  \Closure|int|null  $total
         * @param  \Closure|null  $filterUsing
         * @param  array  $additional
         * @param  int|null|\Closure  $defaultPerPage
         * @param  int|null  $defaultPage
         * @param  string|null  $defaultSortBy
         * @param  bool|null  $defaultDescending
         * @param  array<string>|null  $allowedSorts
         * @return SortableFilterPaginator
         *
         * @throws \InvalidArgumentException
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
            $allowedSorts = null,
        ): SortableFilterPaginator {
            /** @var Builder $this */
            $query = $this;

            $config = Config::get('inertia-data-table');

            $usesQueryState = Request::query($config['table_key_param']) === $tableKey;
            $session = $usesQueryState
                ? []
                : (Request::session()->get("inertia-data-table.{$tableKey}") ?? []);

            // apply filtering (if provided)
            $filter = $usesQueryState
                ? Request::query($config['filter_param'])
                : ($session['filter'] ?? []);
            $filter = is_array($filter) ? $filter : [];

            if ($filterUsing) {
                if (! is_callable($filterUsing)) {
                    throw new \InvalidArgumentException('The filter argument must be a callable (e.g. a closure that accepts the query builder and filter array as parameters).');
                }

                $filterUsing($query, $filter);
            }

            // apply sorting
            $requestedSortBy = $usesQueryState ? Request::query($config['sort_by_param']) : ($session['sortBy'] ?? null);
            $sortBy = $requestedSortBy
                ?? $defaultSortBy
                ?? $config['default_sort_by'];
            if ($allowedSorts !== null && $requestedSortBy !== null && ! in_array($requestedSortBy, $allowedSorts, true)) {
                $sortBy = $defaultSortBy;
            }
            $descending = ($usesQueryState && Request::has($config['descending_param']))
                    ? Request::boolean($config['descending_param'])
                    : ($session['descending'] ?? $defaultDescending ?? $config['default_decending']);
            if ($sortBy !== null) {
                $direction = $descending ? 'desc' : 'asc';
                $query->orderBy($sortBy, $direction);
            }

            // determine pagination parameters
            $pageName ??= $config['page_name_param'];
            $total = value($total) ?? $query->toBase()->getCountForPagination();
            $perPage = ($usesQueryState ? Request::query($config['per_page_param']) : ($session['perPage'] ?? null))
                ?? value($defaultPerPage, $total)
                ?? $config['default_per_page'];
            $all = $perPage <= 0;
            if ($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $usesQueryState
                    ? Paginator::resolveCurrentPage($pageName, $defaultPage)
                    : ($session['page'] ?? $defaultPage ?? 1);
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
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                    'query' => Request::query(),
                ]
            );
        };
    }
}
