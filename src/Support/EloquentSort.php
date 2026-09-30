<?php

namespace StarterSolutions\InertiaDataTable\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

class EloquentSort
{
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
        string $sortBy,
        string $direction,
        bool $resolveRelations = true,
    ): void {
        if (! $resolveRelations) {
            $query->orderBy($sortBy, $direction);

            return;
        }

        $segments = explode('.', $sortBy);
        $modelName = class_basename($model);

        if (count($segments) > 1 && strcasecmp($segments[0], $modelName) === 0) {
            array_shift($segments);
        }

        if (count($segments) === 1) {
            $query->orderBy($segments[0], $direction);

            return;
        }

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("The sort [{$sortBy}] must reference a column or a direct relation column.");
        }

        [$relationName, $column] = $segments;

        if (! method_exists($model, $relationName)) {
            $modelClass = $model::class;

            throw new InvalidArgumentException("The relation [{$relationName}] used by sort [{$sortBy}] does not exist on [{$modelClass}].");
        }

        $relation = Relation::noConstraints(fn () => $model->{$relationName}());

        if (! $relation instanceof Relation) {
            throw new InvalidArgumentException("The method [{$relationName}] used by sort [{$sortBy}] is not an Eloquent relation.");
        }

        $related = $relation->getRelated();
        $relationQuery = $relation->getRelationExistenceQuery(
            $related->newQuery(),
            $query,
            [$related->qualifyColumn($column)],
        );

        $query->orderBy($relationQuery->limit(1), $direction);
    }
}
