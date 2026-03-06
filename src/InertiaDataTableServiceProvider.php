<?php

namespace StarterSolutions\InertiaDataTable;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use StarterSolutions\InertiaDataTable\Macros\DataTableMacros;

class InertiaDataTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inertia-data-table.php', 'inertia-data-table');
    }

    public function boot(): void
    {
        Inertia::share('inertiaDataTable', function () {
            return [
                'queryParams' => [
                    'perPage' => config('inertia-data-table.per_page_param', 'per_page'),
                    'sortBy' => config('inertia-data-table.sort_by_param', 'sort_by'),
                    'descending' => config('inertia-data-table.descending_param', 'descending'),
                    'pageName' => config('inertia-data-table.page_name_param', 'page'),
                ],
                'defaults' => [
                    'perPage' => config('inertia-data-table.default_per_page', 15),
                    'sortBy' => config('inertia-data-table.default_sort_by', 'id'),
                ],
            ];
        });

        // publish config
        $this->publishes([
            __DIR__.'/../config/inertia-data-table.php' => $this->app->configPath('inertia-data-table.php'),
        ], 'inertia-data-table-config');

        // Register the macros
        (new DataTableMacros())->register();
    }
}
