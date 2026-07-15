<?php

namespace Redot\Datatables\Filters;

use Illuminate\Database\Eloquent\Builder;

class NumberFilter extends OperatorFilter
{
    /**
     * The filter's view.
     */
    public string $view = 'datatables::filters.number';

    /**
     * Initialize the filter.
     */
    protected function init(): void
    {
        $this->operators = [
            'equals' => __('datatables::datatable.filters.number.equals'),
            'not_equals' => __('datatables::datatable.filters.number.not_equals'),
            'greater_than' => __('datatables::datatable.filters.number.greater_than'),
            'greater_than_or_equals' => __('datatables::datatable.filters.number.greater_than_or_equals'),
            'less_than' => __('datatables::datatable.filters.number.less_than'),
            'less_than_or_equals' => __('datatables::datatable.filters.number.less_than_or_equals'),
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
            'greater_than' => $query->where($column, '>', $value),
            'greater_than_or_equals' => $query->where($column, '>=', $value),
            'less_than' => $query->where($column, '<', $value),
            'less_than_or_equals' => $query->where($column, '<=', $value),
            default => null,
        };
    }
}
