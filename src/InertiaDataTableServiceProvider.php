<?php

namespace StarterSolutions\InertiaDataTable;

use Illuminate\Support\ServiceProvider;

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
            __DIR__.'/../config/inertia-data-table.php' => config_path('inertia-data-table.php'),
        ], 'inertia-data-table-config');
    }
}
