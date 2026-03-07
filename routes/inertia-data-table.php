<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::prefix('inertia-data-table/state')->middleware(Config::get('inertia-data-table.session_routes_middleware'))->name('inertia-data-table.')->group(function () {
    Route::post('/set', function (Request $request): void {

        $tableKey = $request->input('tableKey');

        if($tableKey) {
            $data = $request->input('data');

            $page = $data['page'] ?? 1;
            $perPage = $data['per_page'] ?? Config::get('inertia-data-table.default_per_page', 15);
            $sortBy = $data['sort_by'] ?? Config::get('inertia-data-table.default_sort_by', 'id');
            $descending = $data['descending'] ?? false;

            Session::put([
                "inertia-data-table.{$tableKey}.page" => $page,
                "inertia-data-table.{$tableKey}.perPage" => $perPage,
                "inertia-data-table.{$tableKey}.sortBy" => $sortBy,
                "inertia-data-table.{$tableKey}.descending" => $descending,
            ]);
        }
    })->name('set');
    
    Route::delete('/drop', function (Request $request): void {

        $tableKey = $request->input('tableKey');

        if($tableKey) {
            Session::forget("inertia-data-table.{$tableKey}");
        }
    })->name('drop');

    Route::delete('/drop/all', fn () => Session::forget("inertia-data-table"))->name('drop.all');
});
