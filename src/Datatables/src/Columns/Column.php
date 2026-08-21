<?php

namespace Redot\Datatables\Columns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Redot\Datatables\Query\RelationSorter;
use Redot\Datatables\Traits\BuildAttributes;
use Redot\Traits\InteractsWithRelations;

class Column
{
    use BuildAttributes;
    use InteractsWithRelations;
    use Macroable;

    /**
     * The column's name.
     */
    public ?string $name = null;

    /**
     * Determine if the column is a relationship.
     */
    public bool $relationship = false;

    /**
     * The column's label.
     */
    public ?string $label = null;

    /**
     * The column's empty value if null.
     */
    public string|Closure $empty = '-';

    /**
     * The column's width.
     */
    public string $width = 'fit-content';

    /**
     * The column's max width.
     */
    public ?string $maxWidth = null;

    /**
     * The column's min width.
     */
    public ?string $minWidth = null;

    /**
     * Determine if the column is a fixed column.
     */
    public bool $fixed = false;

    /**
     * The fixed direction of the column.
     */
    public string $fixedDirection = 'start';

    /**
     * Determine if the column whitespace should be nowrap.
     */
    public bool $nowrap = true;

    /**
     * Determine if the column content is HTML.
     */
    public bool $html = false;

    /**
     * The column's default value.
     */
    public mixed $default = null;

    /**
     * Determine if the column is sortable.
     */
    public bool $sortable = false;

    /**
     * The sorting method for the column.
     */
    public ?Closure $sorter = null;

    /**
     * Determine if the column is searchable.
     */
    public bool $searchable = false;

    /**
     * The searching method for the column.
     */
    public ?Closure $searcher = null;

    /**
     * Determine if the column is visible.
     */
    public bool $visible = true;

    /**
     * The condition callback of the column.
     */
    public ?Closure $condition = null;

    /**
     * Determine if the column is exportable.
     */
    public bool $exportable = true;

    /**
     * The getter method for the column.
     */
    public ?Closure $getter = null;

    /**
     * Create a new column instance.
     */
    public function __construct(?string $name = null, ?string $label = null)
    {
        if ($name) {
            $this->name($name);
        }

        if ($label) {
            $this->label($label);
        }

        $this->init();
    }

    /**
     * Create a new column instance statically.
     */
    public static function make(?string $name = null, ?string $label = null): static
    {
        return new static($name, $label);
    }

    /**
     * Initialize the column.
     */
    public function init(): void
    {
        //
    }

    /**
     * Set the column's name.
     */
    public function name(string $name): static
    {
        $this->name = $name;
        $this->relationship = str_contains($name, '.');

        return $this;
    }

    /**
     * Set the column's label.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the column's empty value if null.
     */
    public function empty(string|Closure $empty): static
    {
        $this->empty = $empty;

        return $this;
    }

    /**
     * Set the column's width.
     */
    public function width(string $width, ?string $min = null, ?string $max = null): static
    {
        $this->width = $width;

        if ($min) {
            $this->minWidth($min);
        }

        if ($max) {
            $this->maxWidth($max);
        }

        return $this;
    }

    /**
     * Set the column's max width.
     */
    public function maxWidth(string $maxWidth): static
    {
        $this->maxWidth = $maxWidth;

        return $this;
    }

    /**
     * Set the column's min width.
     */
    public function minWidth(string $minWidth): static
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    /**
     * Set the column as fixed.
     */
    public function fixed(bool $fixed = true, string $direction = 'start'): static
    {
        $this->fixed = $fixed;
        $this->fixedDirection = $direction;

        return $this;
    }

    /**
     * Set the column's whitespace as nowrap.
     */
    public function nowrap(bool $nowrap = true): static
    {
        $this->nowrap = $nowrap;

        return $this;
    }

    /**
     * Set the column as HTML.
     */
    public function html(bool $html = true): static
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the column's default value.
     */
    public function default(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Set the column as sortable.
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * Set the sorting method for the column.
     */
    public function sorter(Closure $sorter): static
    {
        $this->sorter = $sorter;
        $this->sortable = true;

        return $this;
    }

    /**
     * Set the column as searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Set the searching method for the column.
     */
    public function searcher(Closure $searcher): static
    {
        $this->searcher = $searcher;
        $this->searchable = true;

        return $this;
    }

    /**
     * Set the column as visible.
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Set the column as hidden.
     */
    public function hidden(bool $hidden = true): static
    {
        return $this->visible(! $hidden);
    }

    /**
     * Set the condition callback of the column.
     */
    public function condition(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Determine if the column should be rendered.
     */
    public function shouldRender(mixed ...$args): bool
    {
        return $this->visible && ($this->condition ? call_user_func($this->condition, ...$args) : true);
    }

    /**
     * Set the column as exportable.
     */
    public function exportable(bool $exportable = true): static
    {
        $this->exportable = $exportable;

        return $this;
    }

    /**
     * Set the getter method for the column.
     */
    public function getter(Closure $getter): static
    {
        $this->getter = $getter;

        return $this;
    }

    /**
     * Add the column's constraint to a global search query using OR logic.
     */
    public function applySearch(Builder $query, string $term): void
    {
        if ($this->searcher) {
            call_user_func($this->searcher, $query, $term);

            return;
        }

        if ($this->relationship) {
            $this->orWithRelation($this->name, $query, fn (Builder $query, string $field) => $query->where($field, 'like', "%{$term}%"));

            return;
        }

        $query->orWhere($this->name, 'like', "%{$term}%");
    }

    /**
     * Sort the query by the column in the given direction.
     */
    public function applySort(Builder $query, string $direction): void
    {
        if ($this->sorter) {
            call_user_func($this->sorter, $query, $direction);

            return;
        }

        if ($this->relationship) {
            RelationSorter::apply($query, $this->name, $direction);

            return;
        }

        $query->orderBy($this->name, $direction);
    }

    /**
     * Get the value of the column.
     */
    public function get(Model $row, bool $raw = false): mixed
    {
        $value = $this->name ? data_get($row, $this->name) : $this->default;

        // If a getter is defined, evaluate it with the current value and row.
        if ($this->getter) $value = $this->evaluate($this->getter, $value, $row);

        // If the raw flag is set, return the value without further processing.
        if ($raw) return $value;

        // Apply the default getter to the value and row.
        $value = $this->defaultGetter($value, $row);

        // If the value is null, evaluate the empty value with the current row.
        if (is_null($value)) $value = $this->evaluate($this->empty, $row);

        // If the column is set to HTML, return the value as is; otherwise, escape it for safe output.
        return $this->html ? $value : e($value);
    }

    /**
     * Default getter for the column.
     */
    protected function defaultGetter(mixed $value, Model $row): mixed
    {
        return $value;
    }

    /**
     * Prepare the attributes before building.
     */
    protected function prepareAttributes(?Model $row = null): void
    {
        $this->class('datatable-cell');

        if ($this->fixed) {
            $this->class('fixed-' . $this->fixedDirection);
        }

        $this->css([
            'width: ' . $this->width,
            'min-width: ' . ($this->minWidth ?? $this->width),
            'max-width: ' . ($this->maxWidth ?? $this->width),
        ]);

        if ($this->nowrap) {
            $this->css([
                'white-space: nowrap',
                'overflow: hidden',
                'text-overflow: ellipsis',
            ]);
        }
    }
}
