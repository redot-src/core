<?php

namespace Redot\Datatables\Filters;

use Illuminate\Database\Eloquent\Builder;

class StringFilter extends OperatorFilter
{
    /**
     * The filter's view.
     */
    public string $view = 'datatables::filters.string';

    /**
     * Initialize the filter.
     */
    protected function init(): void
    {
        $this->operators = [
            'equals' => __('datatables::datatable.filters.string.equals'),
            'not_equals' => __('datatables::datatable.filters.string.not_equals'),
            'contains' => __('datatables::datatable.filters.string.contains'),
            'not_contains' => __('datatables::datatable.filters.string.not_contains'),
            'starts_with' => __('datatables::datatable.filters.string.starts_with'),
            'not_starts_with' => __('datatables::datatable.filters.string.not_starts_with'),
            'ends_with' => __('datatables::datatable.filters.string.ends_with'),
            'not_ends_with' => __('datatables::datatable.filters.string.not_ends_with'),
        ];
    }

    /**
     * Apply the given operator constraint to the query column.
     */
    protected function applyOperator(Builder $query, string $column, string $operator, mixed $value): void
    {
        match ($operator) {
            'equals' => $query->where($column, $value),
            'not_equals' => $query->where($column, '!=', $value),
            'contains' => $query->where($column, 'like', "%$value%"),
            'not_contains' => $query->where($column, 'not like', "%$value%"),
            'starts_with' => $query->where($column, 'like', "$value%"),
            'not_starts_with' => $query->where($column, 'not like', "$value%"),
            'ends_with' => $query->where($column, 'like', "%$value"),
            'not_ends_with' => $query->where($column, 'not like', "%$value"),
            default => null,
        };
    }
}
