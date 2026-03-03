<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Parameter Names
    |--------------------------------------------------------------------------
    |
    | The request parameters that control pagination and sorting.
    |
    */
    'per_page_param'    => 'per_page',
    'sort_by_param'     => 'sort_by',
    'descending_param'  => 'descending',
    'page_name_param'   => 'page',

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Fallbacks when no query parameters are present.
    |
    */
    'default_per_page'  => 15,
    'default_sort_by'   => 'id',
];
