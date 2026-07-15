<?php

namespace Redot\Datatables\Filters;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Redot\Datatables\Traits\BuildAttributes;
use Redot\Traits\InteractsWithRelations;

abstract class Filter
{
    use BuildAttributes;
    use InteractsWithRelations;
    use Macroable;

    /**
     * Unique identifier for the filter.
     */
    public string $index;

    /**
     * The filter's livewire key.
     */
    public string $wireKey;

    /**
     * The filter's label.
     */
    public ?string $label = null;

    /**
     * The filter's column(s).
     */
    public string|array|null $column = null;

    /**
     * Determine if the filter columns should be applied with OR logic.
     */
    public bool $or = true;

    /**
     * Override the filter's query.
     */
    public ?Closure $query = null;

    /**
     * Determine if the filter should be applied globally.
     */
    public bool $global = false;

    /**
     * The filter's view.
     */
    public string $view;

    /**
     * Create a new filter instance.
     */
    public function __construct(string|array|null $column = null, ?string $label = null)
    {
        if ($column) {
            $this->column($column);
        }

        if ($label) {
            $this->label($label);
        }

        $this->init();

        $this->index = $this->deriveIndex();
        $this->wireKey ??= sprintf('filtered.%s', $this->index);
    }

    /**
     * Create a new filter instance statically.
     */
    public static function make(string|array|null $column = null, ?string $label = null): static
    {
        return new static($column, $label);
    }

    /**
     * Derive a stable identifier for the filter from its column or label.
     */
    protected function deriveIndex(): string
    {
        $source = $this->column ? implode('-', (array) $this->column) : $this->label;

        return Str::slug($source ?? class_basename(static::class));
    }

    /**
     * Initialize the filter.
     */
    protected function init(): void
    {
        //
    }

    /**
     * Set the filter's label.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the filter's column(s).
     */
    public function column(string|array $column): static
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Set the filter's columns (alias for column with array).
     */
    public function columns(array $columns): static
    {
        $this->column = $columns;

        return $this;
    }

    /**
     * Set the filter's columns to be applied with OR logic.
     */
    public function or(bool $or = true): static
    {
        $this->or = $or;

        return $this;
    }

    /**
     * Set the filter's query.
     */
    public function query(Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Render the filter view.
     */
    public function render(): View
    {
        return view($this->view, ['filter' => $this]);
    }

    /**
     * Get the columns as an array.
     */
    protected function getColumns(): array
    {
        if (is_array($this->column)) {
            return $this->column;
        }

        return $this->column ? [$this->column] : [];
    }

    /**
     * Apply the filter callback to all columns using OR logic.
     */
    protected function applyToColumns(Builder $query, Closure $callback): void
    {
        $columns = $this->getColumns();

        if (empty($columns)) {
            return;
        }

        // Wrap in where to group OR conditions
        $query->where(function (Builder $query) use ($columns, $callback) {
            foreach ($columns as $index => $column) {
                if ($index === 0 || ! $this->or) {
                    $this->withRelation($column, $query, $callback);
                } else {
                    $this->orWithRelation($column, $query, $callback);
                }
            }
        });
    }

    /**
     * Apply the filter to the given query.
     */
    abstract public function apply(Builder $query, mixed $value): void;
}
