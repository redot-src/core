<?php

namespace Redot\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

trait InteractsWithRelations
{
    /**
     * Apply a constraint either on the base query or within a related query using whereHas.
     */
    protected function withRelation(string $column, Builder|QueryBuilder $query, Closure $callback): Builder|QueryBuilder
    {
        return $this->handleRelation($column, $query, $callback, false);
    }

    /**
     * Apply a constraint either on the base query or within a related query using orWhereHas.
     */
    protected function orWithRelation(string $column, Builder|QueryBuilder $query, Closure $callback): Builder|QueryBuilder
    {
        return $this->handleRelation($column, $query, $callback, true);
    }

    /**
     * Centralized handler to reduce duplication between withRelation and orWithRelation.
     */
    protected function handleRelation(string $column, Builder|QueryBuilder $query, Closure $callback, bool $useOr): Builder|QueryBuilder
    {
        if (Str::contains($column, '.') && $this->relationExists($query, $column)) {
            [$relation, $field] = $this->splitRelationColumn($column);

            $constraint = function (Builder $relationQuery) use ($field, $callback) {
                $callback($relationQuery, $field);
            };

            return $useOr
                ? $query->orWhereHas($relation, $constraint)
                : $query->whereHas($relation, $constraint);
        }

        // Fall back to the last segment when the relation cannot be resolved
        $column = Str::afterLast($column, '.');

        if ($useOr) {
            $query->orWhere(fn ($query) => $callback($query, $column));
        } else {
            $callback($query, $column);
        }

        return $query;
    }

    /**
     * Determine if the query can resolve the relation part of the column.
     */
    protected function relationExists(Builder|QueryBuilder $query, string $column): bool
    {
        return $query instanceof Builder && method_exists($query->getModel(), Str::before($column, '.'));
    }

    /**
     * Split a relation column (e.g. users.profile.name) into relation path and field.
     */
    protected function splitRelationColumn(string $column): array
    {
        return [
            Str::beforeLast($column, '.'),
            Str::afterLast($column, '.'),
        ];
    }
}
