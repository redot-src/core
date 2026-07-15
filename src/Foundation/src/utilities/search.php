<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Redot\Traits\InteractsWithRelations;

/**
 * Search the given model.
 */
function search_model(Builder|QueryBuilder $query, array $columns = [], ?string $term = null): Builder|QueryBuilder
{
    $term = trim((string) $term);

    if (! $term) {
        return $query;
    }

    $helper = new class
    {
        use InteractsWithRelations {
            orWithRelation as public;
        }
    };

    return $query->where(function ($query) use ($columns, $term, $helper) {
        foreach ($columns as $column) {
            $helper->orWithRelation($column, $query, fn ($query, string $column) => $query->where($column, 'like', "%{$term}%"));
        }
    });
}
