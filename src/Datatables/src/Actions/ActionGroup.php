<?php

namespace Redot\Datatables\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Redot\Datatables\Traits\BuildAttributes;

class ActionGroup
{
    use BuildAttributes;
    use Macroable;

    /**
     * The label of the action group.
     */
    public ?string $label = null;

    /**
     * The icon of the action group.
     */
    public ?string $icon = null;

    /**
     * The actions of the action group.
     */
    public array $actions = [];

    /**
     * Determine if the action group is visible.
     */
    public bool $visible = true;

    /**
     * The condition callback of the action group.
     */
    public ?Closure $condition = null;

    /**
     * A flag to indicate that the class is an action group.
     */
    public $isActionGroup = true;

    /**
     * Create a new action group instance.
     */
    public function __construct(?string $label = null, ?string $icon = null)
    {
        if ($label) {
            $this->label($label);
        }

        if ($icon) {
            $this->icon($icon);
        }
    }

    /**
     * Create a new action group instance statically.
     */
    public static function make(?string $label = null, ?string $icon = null): static
    {
        return new static($label, $icon);
    }

    /**
     * Show the first couple of actions inline and fold the rest into a group.
     */
    public static function auto(array $actions, ?string $label = null, ?string $icon = null): array
    {
        $offset = is_mobile() ? 0 : 2;
        $count = count(array_filter($actions, fn (Action $action) => $action->visible));

        // If we have $offset + 1 actions total, just show all of them directly
        if ($count <= $offset + 1) return $actions;

        // Display the first $offset actions directly, group the rest if there are more than $offset + 1 total
        $mainActions = array_slice($actions, 0, $offset);
        $remainingActions = array_slice($actions, $offset);

        // If the remaining actions are equal to or less than 1, just show them directly
        if (count($remainingActions) <= 1) return array_merge($mainActions, $remainingActions);

        // Otherwise, show the first $offset actions and group the rest
        return array_merge(
            $mainActions,
            [static::make($label, $icon ?? 'ti ti-dots-vertical')->actions($remainingActions)]
        );
    }

    /**
     * Set the label of the action group.
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the icon of the action group.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the actions of the action group.
     */
    public function actions(array $actions): static
    {
        $this->actions = $actions;

        foreach ($this->actions as $action) {
            $action->grouped(true);
        }

        return $this;
    }

    /**
     * Add an action to the action group.
     */
    public function add(Action $action): static
    {
        $this->actions[] = $action->grouped(true);

        return $this;
    }

    /**
     * Get a copy of the group holding only the given actions.
     */
    public function withActions(array $actions): static
    {
        $clone = clone $this;
        $clone->actions = $actions;

        return $clone;
    }

    /**
     * Find an action in the group by its unique name.
     */
    public function find(string $name): ?Action
    {
        foreach ($this->actions as $action) {
            if ($action->name === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Set the visibility of the action group.
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Set the visibility of the action group to hidden.
     */
    public function hidden(bool $hidden = true): static
    {
        return $this->visible(! $hidden);
    }

    /**
     * Set the condition callback of the action group.
     */
    public function condition(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Determine if the action group should be rendered.
     */
    public function shouldRender(mixed ...$args): bool
    {
        $visible = $this->visible && ($this->condition ? call_user_func($this->condition, ...$args) : true);
        $hasChildren = ! empty(array_filter($this->actions, fn (Action $action) => $action->shouldRender(...$args)));

        return $visible && $hasChildren;
    }

    /**
     * Prepare the attributes before building.
     */
    protected function prepareAttributes(?Model $row = null): void
    {
        $this->class([
            'btn',
            'dropdown-toggle' => $this->label,
            'btn-icon' => $this->icon && ! $this->label,
        ]);

        // Append the dropdown attributes.
        $this->attributes([
            'data-bs-toggle' => 'dropdown',
            'wire:key' => sprintf('action-group-for-%s', $row->getKey()),
        ]);
    }
}
