<?php

namespace StarterSolutions\InertiaDataTable\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SortCallback
{
    public function __invoke(Builder $query, string $direction): void;
}
