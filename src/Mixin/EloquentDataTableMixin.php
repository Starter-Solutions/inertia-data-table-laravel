<?php

namespace StarterSolutions\InertiaDataTable\Mixin;

use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use StarterSolutions\InertiaDataTable\Pagination\SortablePaginator;

/**
 * @method \StarterSolutions\InertiaDataTable\Pagination\SortablePaginator dataTable(string $tableKey, int|null|\Closure $perPage = null, array|string  $columns = ['*'], string|null  $pageName = null, int|null  $page = null, \Closure|int|null  $total = null, string|null  $sortBy = null, bool|null  $descending = null, \Closure|null  $filterUsing = null)
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
         * @param  int|null|\Closure  $perPage
         * @param  array|string  $columns
         * @param  string|null  $pageName
         * @param  int|null  $page
         * @param  \Closure|int|null  $total
         * @param  string|null  $sortBy
         * @param  bool|null  $descending
         * @param  \Closure|null  $filterUsing
         * 
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortablePaginator
         *
         * @throws \InvalidArgumentException
         */
        return function (
            $tableKey, 
            $perPage = null, 
            $columns = ['*'], 
            $pageName = null, 
            $page = null, 
            $total = null, 
            $sortBy = null, 
            $descending = null,
            $filterUsing = null
        ): SortablePaginator  {
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
            if ($filterUsing) {
                if (is_callable($filterUsing)) {
                    $filter = $session['filter'] ?? Request::query($config['filter_param']);
                    $filterUsing($query, $filter);
                } else {
                    throw new \InvalidArgumentException("The filter argument must be a callable (e.g. a closure that accepts the 'query' builder and 'filter' array as parameters).");
                }
            }

            // apply sorting
            $sortBy     = $sortBy     ?? $session['sortBy'] ?? Request::query($config['sort_by_param'],   $config['default_sort_by']);
            $descending = $descending ?? $session['descending'] ?? Request::boolean($config['descending_param'], false);
            $direction  = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);

            // determine pagination parameters
            $pageName   ??= $config['page_name_param'];
            $total      = value($total) ?? $query->toBase()->getCountForPagination();
            $perPage    = value($perPage, $total)  ?? $session['perPage'] ?? Request::query($config['per_page_param'],  $config['default_per_page']);
            $all        = $perPage <= 0;
            if($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $page ?? $session['page'] ?? Paginator::resolveCurrentPage($pageName);
                $query = $query->forPage($page, $perPage);
            }

            $results = $total
                ? $query->get($columns)
                : $this->model->newCollection();
                
            return new SortablePaginator(
                items: $results,
                total: $total,
                perPage: $perPage,
                currentPage: $page,
                sortBy: $sortBy,
                descending: $descending,
                all: $all,
                options: [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                    'query'    => Request::query(),
                ]
            );
        };
    }
}