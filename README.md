# starter-solutions/inertia-data-table

Laravel backend for server-driven data tables with Inertia.js. It adds a `dataTable()` macro to Eloquent builders, query builders, and collections and works with [`@starter-solutions/inertia-data-table-vue`](https://github.com/starter-solutions/inertia-data-table-vue).

## Features

- Pagination, filtering, and sorting owned by the backend
- Independent state for multiple tables through table keys
- URL-query or session-backed table state
- Sort whitelists declared on models
- Recursive sorting through singular Eloquent relations
- Sort aliases for accessors
- Inline callbacks and container-resolved sort classes
- Flat paginator and Laravel `JsonResource` responses

## Installation

```bash
composer require starter-solutions/inertia-data-table
```

Laravel discovers the service provider automatically. To customize parameter names, middleware, or defaults, publish the configuration:

```bash
php artisan vendor:publish --tag=inertia-data-table-config
```

## Quick start

Declare the columns the frontend may sort by:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use StarterSolutions\InertiaDataTable\Attributes\AllowedSorts;

#[AllowedSorts(['id', 'name', 'email', 'created_at'])]
class User extends Model
{
}
```

Return the paginator as an Inertia prop:

```php
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::query()->dataTable(
            tableKey: 'users',
            filterUsing: function (Builder $query, array $filter): void {
                $search = trim((string) ($filter['search'] ?? ''));

                if ($search !== '') {
                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            },
            defaultPerPage: 25,
            defaultSortBy: 'name',
            defaultDescending: false,
        ),
    ]);
});
```

The `tableKey` must match the key passed to the Vue `useDataTable()` composable.

## Allowed sorts

`allowed_sorts` has three distinct states:

- `null`: any base-model column may be sorted
- `[]`: sorting is disabled
- `['id', 'name']`: only the listed public keys are accepted

Prefer an explicit `#[AllowedSorts]` attribute in production:

```php
#[AllowedSorts(['id', 'name', 'email'])]
class User extends Model
{
}
```

You can override the model attribute for one table:

```php
User::query()->dataTable(
    tableKey: 'users',
    allowedSorts: ['id', 'name'],
);
```

Invalid configured columns or relation paths are ignored and logged as structured warnings. The response reports `sort_by: null` when a requested sort could not be applied.

## Sorting related models

Sortable fields are resolved automatically from eager-loaded models:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use StarterSolutions\InertiaDataTable\Attributes\AllowedSorts;

#[AllowedSorts(['id', 'title', 'author_id'])]
class Post extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

#[AllowedSorts(['name', 'email'])]
class User extends Model
{
}

$posts = Post::query()
    ->with('author')
    ->dataTable('posts');
```

The resolved public keys include `author.name` and `author.email`. Nested eager loads such as `author.profile.company.name` are resolved recursively.

Automatic relation sorting supports singular `BelongsTo`, `HasOne`, `HasOneThrough`, and `MorphOne` relations. A multi-value relation such as `HasMany` needs a custom sort callback so the aggregate or selected row is explicit.

## Sorting accessors and aliases

Map an accessor's public key to its backing database column:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

#[AllowedSorts([
    'id',
    'display_name' => 'name',
])]
class User extends Model
{
    protected $appends = ['display_name'];

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => strtoupper($this->name));
    }
}
```

The frontend uses `display_name`; SQL orders by `name`.

## Custom sort callbacks

For one-off SQL expressions, pass a keyed closure:

```php
User::query()->dataTable(
    tableKey: 'users',
    allowedSorts: [
        'name',
        'name_length' => fn (Builder $query, string $direction) =>
            $query->orderByRaw("length(name) {$direction}"),
    ],
);
```

PHP attributes cannot contain closures. Use an invokable class for reusable or attribute-based custom sorts:

```php
use Illuminate\Database\Eloquent\Builder;
use StarterSolutions\InertiaDataTable\Contracts\SortCallback;

final class NameLengthSort implements SortCallback
{
    public function __invoke(Builder $query, string $direction): void
    {
        $query->orderByRaw("length(name) {$direction}");
    }
}
```

```php
#[AllowedSorts([
    'name',
    'name_length' => NameLengthSort::class,
])]
class User extends Model
{
}
```

Sort classes are resolved through Laravel's container and may use constructor injection. Callback sorts must have a public string key.

## Filtering and additional metadata

The filter callback receives the active filter values. Extra values can be returned beside the pagination metadata:

```php
User::query()->dataTable(
    tableKey: 'users',
    filterUsing: fn (Builder $query, array $filter) => $query
        ->when($filter['status'] ?? null, fn (Builder $query, string $status) =>
            $query->where('status', $status)),
    additional: [
        'filters' => [
            'statuses' => ['active', 'invited', 'disabled'],
        ],
    ],
);
```

Vue can read this with `additional` or `getAdditional('filters.statuses', [])`.

## Query builders and collections

The macro is also available on the database query builder:

```php
$users = DB::table('users')->dataTable(
    tableKey: 'users',
    allowedSorts: ['id', 'name', 'email'],
);
```

Collections are filtered, sorted, and paginated in memory. Dot notation works for nested values:

```php
$users = collect($items)->dataTable(
    tableKey: 'users',
    allowedSorts: ['name', 'profile.city'],
);
```

Relation discovery, accessor aliases, and `SortCallback` classes apply only to Eloquent builders.

## Multiple tables

Use a unique table key and prop for each table:

```php
return Inertia::render('Dashboard', [
    'users' => User::query()->dataTable('users'),
    'orders' => Order::query()->dataTable('orders'),
]);
```

Only the table identified by the request's `tableKey` consumes URL query state. Other tables use their independent session state.

## JsonResource wrapping

Both flat paginator data and Laravel resource collections are supported by the Vue package:

```php
'users' => UserResource::collection(
    User::query()->dataTable('users')
),
```

## Configuration

Published `config/inertia-data-table.php` options include:

```php
return [
    'session_routes_middleware' => ['web'],
    'table_key_param' => 'tableKey',
    'per_page_param' => 'per_page',
    'sort_by_param' => 'sort_by',
    'descending_param' => 'descending',
    'page_name_param' => 'page',
    'filter_param' => 'filter',
    'default_per_page' => 15,
    'default_sort_by' => 'id',
    'default_descending' => true,
];
```

## License

MIT © Starter Solutions
