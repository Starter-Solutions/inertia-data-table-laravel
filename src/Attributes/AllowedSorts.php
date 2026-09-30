<?php

namespace StarterSolutions\InertiaDataTable\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use StarterSolutions\InertiaDataTable\Contracts\SortCallback;

#[Attribute(Attribute::TARGET_CLASS)]
class AllowedSorts
{
    /** @var array<class-string, array<int|string, string>|null> */
    private static array $resolved = [];

    /** @var array<int|string, string> */
    public array $columns;

    /**
     * @param  array<int|string, string>|string  ...$columns
     */
    public function __construct(array|string ...$columns)
    {
        $this->columns = is_array($columns[0]) ? $columns[0] : $columns;
    }

    /**
     * @param  object|class-string  $model
     * @return array<int|string, string>|null
     */
    public static function resolve(object|string $model): ?array
    {
        $class = is_object($model) ? $model::class : $model;

        if (array_key_exists($class, self::$resolved)) {
            return self::$resolved[$class];
        }

        $reflection = new ReflectionClass($class);

        do {
            $attributes = $reflection->getAttributes(self::class);

            if ($attributes !== []) {
                return self::$resolved[$class] = $attributes[0]->newInstance()->columns;
            }
        } while ($reflection = $reflection->getParentClass());

        return self::$resolved[$class] = null;
    }

    /**
     * Resolve the model's allowed sorts and add the declared sorts of eager
     * loaded relations using dot notation.
     *
     * @return array<int|string, string>|null
     */
    public static function resolveForQuery(Builder $query): ?array
    {
        $columns = self::resolve($query->getModel());

        // A missing root attribute retains the existing unrestricted behavior.
        if ($columns === null) {
            return null;
        }

        foreach (array_keys($query->getEagerLoads()) as $relationPath) {
            $related = self::relatedModel($query->getModel(), $relationPath);
            $relatedColumns = self::resolve($related);

            if ($relatedColumns === null) {
                continue;
            }

            foreach ($relatedColumns as $key => $column) {
                if (is_int($key)) {
                    $columns[] = "{$relationPath}.{$column}";
                } else {
                    $columns["{$relationPath}.{$key}"] = self::isCallbackClass($column)
                        ? $column
                        : "{$relationPath}.{$column}";
                }
            }
        }

        return $columns;
    }

    private static function isCallbackClass(string $definition): bool
    {
        return class_exists($definition) && is_subclass_of($definition, SortCallback::class);
    }

    private static function relatedModel(Model $model, string $relationPath): Model
    {
        foreach (explode('.', $relationPath) as $relationName) {
            if (! method_exists($model, $relationName)) {
                $modelClass = $model::class;

                throw new \InvalidArgumentException("The eager loaded relation [{$relationPath}] does not exist on [{$modelClass}].");
            }

            $relation = Relation::noConstraints(fn () => $model->{$relationName}());

            if (! $relation instanceof Relation) {
                throw new \InvalidArgumentException("The eager loaded method [{$relationName}] is not an Eloquent relation.");
            }

            $model = $relation->getRelated();
        }

        return $model;
    }
}
