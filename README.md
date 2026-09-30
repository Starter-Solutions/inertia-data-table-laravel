# starter-solutions/inertia-data-table

> ⚠️ This is the **Laravel backend package** for  
> **@starter-solutions/inertia-data-table-vue (Vue 3 + Inertia companion package)**.
>
> If you are looking for the frontend package, visit:  
> 👉 https://github.com/starter-solutions/inertia-data-table-vue

---

## 📦 Overview

`starter-solutions/inertia-data-table` provides a clean and consistent way to handle:

- Server-driven pagination
- Sorting
- Query parameter management
- Multiple independent tables per page
- Inertia-powered table state synchronization

It extends Laravel’s query builder with a data-table macro that integrates seamlessly with Inertia.js and Vue 3.

This package is designed to work together with:

**Frontend (Vue 3 + Inertia):**  
https://github.com/starter-solutions/inertia-data-table-vue

Together they provide a structured, reusable approach to building sortable and paginated data tables in Laravel + Inertia applications.

---

## Sorting by relation columns

Eloquent tables can sort by a column from a directly related model. Declare sortable columns on both models and eager load the relation:

```php
#[AllowedSorts(['id', 'name'])]
class User extends Model {}

#[AllowedSorts(['display_name', 'city'])]
class Profile extends Model {}

$users = User::query()->with('profile')->dataTable('users');
```

The resolved sort keys are `id`, `name`, `profile.display_name`, and `profile.city`. Nested eager loads are resolved recursively the same way. Relations without an `#[AllowedSorts]` attribute expose no sortable columns. An explicit `allowedSorts` argument still overrides automatic resolution.

Both `profile.display_name` and the optional model-qualified form `User.profile.display_name` can be used when explicitly allowed. Relation sorts use a correlated subquery, so they do not duplicate rows in the base table.

### Accessors and custom sorts

An accessor can be mapped to the database column that represents its sortable value:

```php
#[AllowedSorts([
    'id',
    'display_name' => 'name',
])]
class User extends Model {}
```

The frontend uses `display_name`, while the query orders by `name`. For computed values that need a custom SQL expression, pass a keyed callback:

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

Callbacks must use a string key. The callback key—not its implementation—is exposed through `allowed_sorts`.

PHP attributes cannot contain closures, but they can reference an invokable class implementing `SortCallback`:

```php
use StarterSolutions\InertiaDataTable\Contracts\SortCallback;

#[AllowedSorts([
    'name',
    'name_length' => NameLengthSort::class,
])]
class User extends Model {}

final class NameLengthSort implements SortCallback
{
    public function __invoke(Builder $query, string $direction): void
    {
        $query->orderByRaw("length(name) {$direction}");
    }
}
```

Sort callback classes are resolved through Laravel's container, so they may use constructor injection. A class that does not implement `SortCallback`, or cannot be resolved, is logged and ignored.

Automatic relation sorting supports singular `BelongsTo`, `HasOne`, `HasOneThrough`, and `MorphOne` relations. Multi-value relations such as `HasMany` require a custom sort callback so the desired aggregate or related row is explicit.

`allowed_sorts` preserves three distinct states: `null` means unrestricted base-model columns, `[]` disables sorting, and a non-empty array is an explicit whitelist.

Before applying a regular or relation-column sort, the package verifies that the target exists. Invalid allowed sorts are ignored, `sort_by` is returned as `null`, and a structured warning is logged with the table key, model, table, sort key, and reason. This avoids database-specific SQL failures in production while keeping configuration problems observable.

---

## 🚀 Installation

```bash
composer require starter-solutions/inertia-data-table
```

## 🧠 Concept

The package introduces a table key–based system:

- Each table has a unique identifier.
- Pagination and sorting state are scoped to that identifier.
- Multiple tables can exist on the same Inertia page without conflicts.
- The backend remains the single source of truth for data ordering and limits.

This approach keeps controllers clean while maintaining predictable frontend behavior.

---

## 🎯 Goals

- Provide a Laravel-native API similar to `->paginate()`
- Avoid manual query string management
- Support multiple tables on one page
- Keep pagination logic centralized
- Maintain full compatibility with Inertia.js

---

## 🐛 Issues & Support

Bug reports and feature requests are welcome.

Please open an issue in this repository:

👉 https://github.com/starter-solutions/inertia-data-table/issues

---

## 📄 License

MIT © Starter Solutions
