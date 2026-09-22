<?php

namespace StarterSolutions\InertiaDataTable\Attributes;

use Attribute;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
class AllowedSorts
{
    /** @var array<class-string, array<string>|null> */
    private static array $resolved = [];

    /** @var array<string> */
    public array $columns;

    /**
     * @param  array<string>|string  ...$columns
     */
    public function __construct(array|string ...$columns)
    {
        $this->columns = is_array($columns[0]) ? $columns[0] : $columns;
    }

    /**
     * @param  object|class-string  $model
     * @return array<string>|null
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
}
