<?php

namespace StarterSolutions\InertiaDataTable\Macros;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use StarterSolutions\InertiaDataTable\Pagination\SortablePaginator;

class DataTableMacros
{
    private const MACRO_NAME = 'dataTable';
    private const CONFIG_NAME = 'inertia-data-table';

    /**
     * Register all the dataTable macros.
     */
    public function register(): void
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
         * @param  bool  $descending
         * 
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortablePaginator
         *
         * @throws \InvalidArgumentException
         */
        EloquentBuilder::macro(self::MACRO_NAME, function (
            $tableKey, 
            $perPage = null, 
            $columns = ['*'], 
            $pageName = null, 
            $page = null, 
            $total = null, 
            $sortBy = null, 
            $descending = false
        ): SortablePaginator  {
            /** @var \Illuminate\Database\Eloquent\Builder $this */
            $query = $this;
            
            $config = Config::get(self::CONFIG_NAME);

            // apply sorting
            $sortBy     = $sortBy     ?? Request::query($config['sort_by_param'],   $config['default_sort_by']);
            $descending = $descending ?? Request::boolean($config['descending_param'], false);
            $direction  = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);

            // determine pagination parameters
            $pageName   ??= $config['page_name_param'];
            $total      = value($total) ?? $query->toBase()->getCountForPagination();
            $perPage    = value($perPage, $total)  ?? Request::query($config['per_page_param'],  $config['default_per_page']);
            $all        = $perPage <= 0;
            if($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $page ?: Paginator::resolveCurrentPage($pageName);
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
        });

        /**
         * Paginate the given query.
         *
         * @param  string  $tableKey
         * @param  int|null|\Closure  $perPage
         * @param  string|\Illuminate\Contracts\Database\Query\Expression|array<string|\Illuminate\Contracts\Database\Query\Expression>  $columns
         * @param  string|null  $pageName
         * @param  int|null  $page
         * @param  string|null  $sortBy
         * @param  bool  $descending
         * 
         * @return \StarterSolutions\InertiaDataTable\Pagination\SortablePaginator    
         */
        QueryBuilder::macro(self::MACRO_NAME, function (
            $tableKey, 
            $perPage = null, 
            $columns = ['*'], 
            $pageName = null, 
            $page = null, 
            $total = null, 
            $sortBy = null, 
            $descending = false
        ): SortablePaginator{
            /** @var \Illuminate\Database\Query\Builder $this */
            $query  = $this;

            $config = Config::get(self::CONFIG_NAME);

            // apply sorting
            $sortBy = $sortBy ?? Request::query($config['sort_by_param'],   $config['default_sort_by']);
            $descending = $descending ?? Request::boolean($config['descending_param'], false);
            $direction = $descending ? 'desc' : 'asc';
            $query->orderBy($sortBy, $direction);

            // determine pagination parameters
            $pageName = $pageName ?? $config['page_name_param'];
            $total = value($total) ?? $query->getCountForPagination();
            $perPage = value($perPage, $total)  ?? Request::query($config['per_page_param'],  $config['default_per_page']);
            $all = $perPage <= 0;
            if($all) {
                // fetch all items (ignoring pagination)
                $page = 1; // always page 1 when perPage <= 0 (i.e. "all")
            } else {
                $page = $page ?: Paginator::resolveCurrentPage($pageName);
                $query = $query->forPage($page, $perPage);
            }

            $results = $total
                ? $query->get($columns)
                : new Collection;

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
        });
    }
}