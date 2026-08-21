<?php

namespace Redot\Datatables\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class RelationSorter
{
    /**
     * Sort the query by a column reached through one or more relations.
     */
    public static function apply(Builder $query, string $column, string $direction): void
    {
        $relations = explode('.', $column);
        $field = array_pop($relations);

        // The name of the aggregate column
        $name = Str::snake(implode(' ', [...$relations, $field]));

        if (count($relations) === 1) {
            $query->withAggregate($relations[0], $field);
        } else {
            if (is_null($query->getQuery()->columns)) {
                $query->select($query->getModel()->qualifyColumn('*'));
            }

            $query->selectSub(static::subquery($query, $relations, $field)->limit(1), $name);
        }

        $query->orderBy($name, $direction);
    }

    /**
     * Build a correlated subquery for a column reached through nested relations.
     */
    protected static function subquery(Builder $parentQuery, array $relations, string $field): Builder
    {
        $relationName = array_shift($relations);
        $relation = Relation::noConstraints(fn () => $parentQuery->getModel()->{$relationName}());
        $relatedQuery = $relation->getRelated()->newQuery();

        if (count($relations) === 0) {
            $query = $relation->getRelationExistenceQuery(
                $relatedQuery,
                $parentQuery,
                $relation->getRelated()->qualifyColumn($field),
            );
        } else {
            $query = $relation->getRelationExistenceQuery($relatedQuery, $parentQuery);
            $query->select([])->selectSub(
                static::subquery($relatedQuery, $relations, $field)->limit(1),
                'nested_relation_value',
            );
        }

        return $query->mergeConstraintsFrom($relation->getQuery());
    }
}
