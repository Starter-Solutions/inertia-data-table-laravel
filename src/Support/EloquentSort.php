<?php

namespace StarterSolutions\InertiaDataTable\Support;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use StarterSolutions\InertiaDataTable\Contracts\SortCallback;
use Throwable;

class EloquentSort
{
    /** @var array<string, bool> */
    private static array $columnExists = [];

    /**
     * @param  array<int|string, string|Closure>|null  $definitions
     * @return array<string>|null
     */
    public static function keys(?array $definitions): ?array
    {
        if ($definitions === null) {
            return null;
        }

        $keys = [];

        foreach ($definitions as $key => $definition) {
            if (is_int($key) && ($definition instanceof Closure || self::isCallbackClass($definition))) {
                Log::warning('Inertia Data Table ignored an unkeyed callback sort.', [
                    'reason' => 'callback_sort_requires_string_key',
                    'callback' => is_string($definition) ? $definition : Closure::class,
                ]);

                continue;
            }

            $keys[] = is_int($key) ? $definition : $key;
        }

        return $keys;
    }

    /**
     * @param  array<int|string, string|Closure>|null  $definitions
     */
    public static function definition(?array $definitions, string $key): string|Closure
    {
        if ($definitions === null || ! array_key_exists($key, $definitions)) {
            return $key;
        }

        return $definitions[$key];
    }

    /**
     * Apply a regular column sort or a sort on a directly related model.
     *
     * Relation sorts use the relation column as a correlated subquery, so the
     * base query is not multiplied by a join. Both `profile.display_name` and
     * the model-qualified `User.profile.display_name` notation are accepted.
     */
    public static function apply(
        Builder $query,
        Model $model,
        string|Closure $sortBy,
        string $direction,
        bool $resolveRelations = true,
        array $context = [],
    ): bool {
        if ($sortBy instanceof Closure) {
            $sortBy($query, $direction);

            return true;
        }

        if (class_exists($sortBy)) {
            if (! is_subclass_of($sortBy, SortCallback::class)) {
                return self::logInvalidSort($model, $sortBy, 'invalid_callback_class', $context, [
                    'callback' => $sortBy,
                ]);
            }

            try {
                $callback = Container::getInstance()->make($sortBy);
            } catch (Throwable $exception) {
                return self::logInvalidSort($model, $sortBy, 'callback_resolution_failed', $context, [
                    'callback' => $sortBy,
                    'exception' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]);
            }

            $callback($query, $direction);

            return true;
        }

        $segments = explode('.', $sortBy);
        $modelName = class_basename($model);

        if (count($segments) > 1 && strcasecmp($segments[0], $modelName) === 0) {
            array_shift($segments);
        }

        if (count($segments) > 1 && $segments[0] === $model->getTable()) {
            array_shift($segments);
        }

        if (count($segments) === 1) {
            if (! self::columnExists($model, $segments[0])) {
                return self::logInvalidSort($model, $sortBy, 'column_not_found', $context, [
                    'column' => $segments[0],
                ]);
            }

            $query->orderBy($segments[0], $direction);

            return true;
        }

        if (! $resolveRelations) {
            return self::logInvalidSort($model, $sortBy, 'relation_sort_requires_allowlist', $context);
        }

        $column = array_pop($segments);
        $relationQuery = self::relationValueQuery(
            $query,
            $model,
            $segments,
            $column,
            $sortBy,
            $context,
        );

        if ($relationQuery === false) {
            return false;
        }

        $query->orderBy($relationQuery->limit(1), $direction);

        return true;
    }

    /**
     * @param  array<string>  $relations
     */
    private static function relationValueQuery(
        Builder $parentQuery,
        Model $parentModel,
        array $relations,
        string $column,
        string $sortBy,
        array $context,
    ): Builder|false {
        $relationName = array_shift($relations);

        if ($relationName === null || ! method_exists($parentModel, $relationName)) {
            return self::logInvalidSort($parentModel, $sortBy, 'relation_not_found', $context, [
                'relation' => $relationName,
            ]);
        }

        $relation = Relation::noConstraints(fn () => $parentModel->{$relationName}());

        if (! $relation instanceof Relation) {
            return self::logInvalidSort($parentModel, $sortBy, 'method_is_not_relation', $context, [
                'relation' => $relationName,
            ]);
        }

        if (! self::isSingularRelation($relation)) {
            return self::logInvalidSort($parentModel, $sortBy, 'unsupported_relation_type', $context, [
                'relation' => $relationName,
                'relation_type' => $relation::class,
            ]);
        }

        $related = $relation->getRelated();

        $relationQuery = $relation->getRelationExistenceQuery(
            $related->newQuery(),
            $parentQuery,
        )->mergeConstraintsFrom($relation->getQuery());

        if ($relations !== []) {
            $nestedQuery = self::relationValueQuery(
                $relationQuery,
                $related,
                $relations,
                $column,
                $sortBy,
                $context,
            );

            if ($nestedQuery === false) {
                return false;
            }

            return $relationQuery
                ->select([])
                ->selectSub($nestedQuery->limit(1), 'inertia_data_table_sort_value');
        }

        if (! self::columnExists($related, $column)) {
            return self::logInvalidSort($related, $sortBy, 'related_column_not_found', $context, [
                'column' => $column,
                'relation' => $relationName,
            ]);
        }

        return $relationQuery->select($related->qualifyColumn($column));
    }

    private static function columnExists(Model $model, string $column): bool
    {
        $cacheKey = implode(':', [
            spl_object_id($model->getConnection()),
            $model->getTable(),
            $column,
        ]);

        return self::$columnExists[$cacheKey] ??= $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($model->getTable(), $column);
    }

    private static function isCallbackClass(string|Closure $definition): bool
    {
        return is_string($definition)
            && class_exists($definition)
            && is_subclass_of($definition, SortCallback::class);
    }

    private static function isSingularRelation(Relation $relation): bool
    {
        return $relation instanceof BelongsTo
            || $relation instanceof HasOne
            || $relation instanceof HasOneThrough
            || $relation instanceof MorphOne;
    }

    private static function logInvalidSort(
        Model $model,
        string $sortBy,
        string $reason,
        array $context,
        array $details = [],
    ): false {
        Log::warning('Inertia Data Table ignored an invalid allowed sort.', [
            ...$context,
            'model' => $model::class,
            'table' => $model->getTable(),
            'sort' => $context['sort_key'] ?? $sortBy,
            'reason' => $reason,
            ...$details,
        ]);

        return false;
    }
}
