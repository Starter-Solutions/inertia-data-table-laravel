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
    // new: query‑string key for “per page” size
    'per_page_param'    => 'per_page',

    // existing: query‑string key for sort‑by
    'sort_by_param'     => 'sort_by',

    // existing: query‑string key for asc/desc
    'descending_param'  => 'descending',

    // new: query‑string key for page number
    'page_name_param'   => 'page',

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Fallbacks when no query parameters are present.
    |
    */
    // how many items per page by default
    'default_per_page'  => 15,

    // which column to sort by if none supplied
    'default_sort_by'   => 'id',
];
