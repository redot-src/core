<?php

namespace Redot\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class QueryFilters
{
    /**
     * Resolve the filter definitions for the query builder UI.
     */
    public static function resolve(?string $model = null, ?array $filters = null): array
    {
        return (new self)->definitions($model, $filters);
    }

    /**
     * Apply a rule tree to the given query.
     */
    public static function query(array $rules, EloquentBuilder|BaseQueryBuilder|null $query = null): EloquentBuilder|BaseQueryBuilder
    {
        return (new self)->apply($rules, $query ?? DB::query());
    }

    /**
     * Build filter definitions from a model or a raw filter map.
     */
    public function definitions(?string $model, ?array $filters): array
    {
        $filters ??= $model ? $this->modelFilters($model) : [];

        $resolved = [];
        foreach ($filters as $key => $definition) {
            $resolved[] = $this->buildDefinition((string) $key, $definition);
        }

        return $resolved;
    }

    /**
     * Apply a rule tree to the query.
     */
    public function apply(array $rules, EloquentBuilder|BaseQueryBuilder $query): EloquentBuilder|BaseQueryBuilder
    {
        if (blank($rules['rules'] ?? [])) return $query;

        return $query->where(fn ($nested) => $this->applyGroup($nested, $rules));
    }

    /**
     * Derive filter definitions from a model's table schema.
     */
    protected function modelFilters(string $model): array
    {
        if (! class_exists($model)) {
            throw new InvalidArgumentException("Query filters model [{$model}] does not exist.");
        }

        if (method_exists($model, 'getTableSchema')) return $model::getTableSchema();

        $instance = new $model;
        if (! $instance instanceof Model) {
            throw new InvalidArgumentException(sprintf('Query filters class [%s] must extend %s.', $model, Model::class));
        }

        $hidden = $instance->getHidden();
        $columns = $instance->getConnection()->getSchemaBuilder()->getColumns($instance->getTable());

        $filters = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            if (in_array($name, $hidden, true)) continue;

            $type = $this->mapColumnType((string) ($column['type'] ?? ''));
            if ($type === null) continue;

            $filters[$name] = [
                'title' => __(Str::headline($name)),
                'type' => $type,
            ];
        }

        return $filters;
    }

    /**
     * Map a database column type to a filter type.
     */
    protected function mapColumnType(string $rawType): ?string
    {
        $type = strtolower($rawType);

        return match (true) {
            $type === 'date' => 'date',
            $type === 'time' => 'time',
            in_array($type, ['json', 'jsonb'], true) => null,
            Str::contains($type, ['datetime', 'timestamp']) => 'datetime',
            Str::contains($type, 'int') => 'integer',
            Str::contains($type, ['decimal', 'numeric', 'float', 'double', 'real']) => 'double',
            Str::contains($type, ['char', 'text', 'string', 'enum', 'set', 'uuid']) => 'string',
            Str::contains($type, 'bool') => 'boolean',
            default => null,
        };
    }

    /**
     * Build a single filter definition for the UI.
     */
    protected function buildDefinition(string $key, array $definition): array
    {
        $type = $definition['type'];
        $hasValuesKey = isset($definition['values']);
        $values = $this->resolveValues($definition);

        $prefix = isset($definition['query']) ? 'query:' : 'field:';
        $field = $prefix . (isset($definition['query']) ? $definition['query'] : $key);

        $filter = [
            'id' => hash('sha256', $key),
            'field' => encrypt($field),
            'label' => $definition['title'],
            'type' => $type,
            'input' => $hasValuesKey ? 'select' : $this->defaultInputFor($type),
            'operators' => $this->operatorsFor($type),
        ];

        if ($filter['input'] === 'select') {
            $filter['plugin'] = 'tomselect';
            $filter['input_event'] = 'change.tomselect update.tomselect';
            $filter['operators'] = ['equal', 'not_equal', 'is_null', 'is_not_null'];
        }

        if (in_array($type, ['date', 'datetime', 'time'], true)) {
            $filter['plugin'] = 'datepicker';
            $filter['input_event'] = 'change.td update.td';
            $filter['plugin_config'] = ['type' => $type];
        }

        if ($values !== null) {
            $filter['values'] = $values;
        } elseif ($type === 'boolean') {
            $filter['values'] = [true => __('Yes'), false => __('No')];
        }

        return $filter;
    }

    /**
     * Normalize a definition's `values` (callable, iterable, or absent).
     */
    protected function resolveValues(array $definition): ?array
    {
        if (! isset($definition['values'])) return null;

        $values = $definition['values'];
        if (is_callable($values)) $values = $values();

        return is_iterable($values) ? Arr::from($values) : null;
    }

    /**
     * Default HTML input kind for a filter type.
     */
    protected function defaultInputFor(string $type): string
    {
        return match ($type) {
            'boolean' => 'select',
            'integer', 'double' => 'number',
            default => 'text',
        };
    }

    /**
     * Allowed operators for a filter type.
     */
    protected function operatorsFor(string $type): array
    {
        return match ($type) {
            'integer', 'double' => ['equal', 'not_equal', 'in', 'not_in', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'between', 'not_between', 'is_null', 'is_not_null'],
            'string' => ['equal', 'not_equal', 'in', 'not_in', 'begins_with', 'not_begins_with', 'contains', 'not_contains', 'ends_with', 'not_ends_with', 'is_empty', 'is_not_empty', 'is_null', 'is_not_null'],
            'date', 'datetime', 'time' => ['equal', 'not_equal', 'in', 'not_in', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'between', 'not_between', 'is_null', 'is_not_null'],
            'boolean' => ['equal', 'not_equal', 'is_null', 'is_not_null'],
            default => [],
        };
    }

    /**
     * Apply a rules group to the query.
     */
    protected function applyGroup(EloquentBuilder|BaseQueryBuilder $query, array $group): void
    {
        $condition = $this->resolveCondition($group);
        $rules = array_values(array_filter($group['rules'] ?? [], 'is_array'));

        foreach ($rules as $index => $rule) {
            $boolean = $index === 0 ? 'and' : $condition;

            if (isset($rule['rules'])) {
                $this->applyNestedGroup($query, $rule, $boolean);
            } else {
                $this->applyRule($query, $rule, $boolean);
            }
        }
    }

    /**
     * Wrap a nested rules group inside the query.
     */
    protected function applyNestedGroup(EloquentBuilder|BaseQueryBuilder $query, array $group, string $boolean): void
    {
        $method = $boolean === 'or' ? 'orWhere' : 'where';

        $query->{$method}(fn ($nested) => $this->applyGroup($nested, $group));
    }

    /**
     * Apply a single rule to the query.
     */
    protected function applyRule(EloquentBuilder|BaseQueryBuilder $query, array $rule, string $boolean): void
    {
        [$prefix, $field] = explode(':', $this->decryptField($rule), 2);
        $operator = $rule['operator'] ?? null;
        $value = $rule['value'] ?? null;

        // If the field is a query, evaluate it as a raw SQL expression.
        if ($prefix === 'query') $field = DB::raw("({$field})");

        match ($operator) {
            'equal' => $query->where($field, '=', $value, $boolean),
            'not_equal' => $query->where($field, '!=', $value, $boolean),
            'in' => $query->whereIn($field, Arr::wrap($value), $boolean),
            'not_in' => $query->whereIn($field, Arr::wrap($value), $boolean, true),
            'less' => $query->where($field, '<', $value, $boolean),
            'less_or_equal' => $query->where($field, '<=', $value, $boolean),
            'greater' => $query->where($field, '>', $value, $boolean),
            'greater_or_equal' => $query->where($field, '>=', $value, $boolean),
            'between' => $query->whereBetween($field, Arr::wrap($value), $boolean),
            'not_between' => $query->whereBetween($field, Arr::wrap($value), $boolean, true),
            'begins_with' => $query->where($field, 'like', $value . '%', $boolean),
            'not_begins_with' => $query->where($field, 'not like', $value . '%', $boolean),
            'contains' => $query->where($field, 'like', '%' . $value . '%', $boolean),
            'not_contains' => $query->where($field, 'not like', '%' . $value . '%', $boolean),
            'ends_with' => $query->where($field, 'like', '%' . $value, $boolean),
            'not_ends_with' => $query->where($field, 'not like', '%' . $value, $boolean),
            'is_empty' => $query->where($field, '=', '', $boolean),
            'is_not_empty' => $query->where($field, '!=', '', $boolean),
            'is_null' => $query->whereNull($field, $boolean),
            'is_not_null' => $query->whereNull($field, $boolean, true),
            default => throw new InvalidArgumentException(sprintf('Unsupported query filter operator [%s].', $operator ?? 'null')),
        };
    }

    /**
     * Resolve the AND/OR condition of a rules group.
     */
    protected function resolveCondition(array $group): string
    {
        return strtolower($group['condition'] ?? 'and') === 'or' ? 'or' : 'and';
    }

    /**
     * Decrypt the field name from a rule.
     */
    protected function decryptField(array $rule): string
    {
        if (! isset($rule['field']) || ! is_string($rule['field'])) {
            throw new InvalidArgumentException('Query filter rule is missing a valid field.');
        }

        return decrypt($rule['field']);
    }
}
