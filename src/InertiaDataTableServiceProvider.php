<?php

namespace StarterSolutions\InertiaDataTable;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use StarterSolutions\InertiaDataTable\Macros\DataTableMacros;

class InertiaDataTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/inertia-data-table.php', 'inertia-data-table');
    }

    public function boot(): void
    {
        // publish config
        $this->publishes([
            __DIR__.'/../config/inertia-data-table.php' => App::configPath('inertia-data-table.php'),
        ], 'inertia-data-table-config');

        // Register the macros
        (new DataTableMacros())->register();
    }
}
