<?php

namespace Redot\Datatables\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class OperatorFilter extends Filter
{
    /**
     * Filter operations.
     */
    public array $operators = [];

    /**
     * Apply the filter to the given query.
     */
    public function apply(Builder $query, mixed $value): void
    {
        $operator = isset($value['operator']) ? $value['operator'] : 'equals';
        $value = isset($value['value']) ? $value['value'] : '';

        // Early return if the value is empty or not scalar (e.g. crafted array input).
        if (! is_scalar($value) || $value === '') {
            return;
        }

        // Apply the filter to all columns with OR logic.
        $this->applyToColumns($query, function (Builder $query, string $column) use ($operator, $value) {
            $this->applyOperator($query, $column, $operator, $value);
        });
    }

    /**
     * Apply the given operator constraint to the query column.
     */
    abstract protected function applyOperator(Builder $query, string $column, string $operator, mixed $value): void;
}
