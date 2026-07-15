<?php

namespace Redot\Sidebar;

use Closure;
use Illuminate\Support\Traits\Macroable;

class Item
{
    use Macroable;

    /**
     * The parent of the item.
     */
    public ?Item $parent = null;

    /**
     * The title of the item.
     */
    public ?string $title = null;

    /**
     * The icon of the item.
     */
    public ?string $icon = null;

    /**
     * The route of the item.
     */
    public ?string $route = null;

    /**
     * The URL of the item.
     */
    public ?string $url = null;

    /**
     * Determine if the item is external.
     */
    public bool $external = false;

    /**
     * The parameters of the item.
     */
    public array $parameters = [];

    /**
     * The children of the item.
     */
    public array $children = [];

    /**
     * Determine if the item is active.
     */
    public bool $active = false;

    /**
     * Determine if the item is visible.
     */
    public bool $visible = true;

    /**
     * The condition callback of the item.
     */
    public ?Closure $condition = null;

    /**
     * The badge callable of the item.
     */
    public ?Closure $badge = null;

    /**
     * Create a new sidebar item instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Set the title of the item.
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the icon of the item.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the route of the item.
     */
    public function route(string $route, array $parameters = []): static
    {
        $this->route = $route;
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Set the URL of the item.
     */
    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the external status of the item.
     */
    public function external(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    /**
     * Set the children of the item.
     */
    public function children(array $children): static
    {
        $this->children = $children;

        // Assign the parent to each child.
        foreach ($this->children as $child) {
            $child->parent = $this;
        }

        return $this;
    }

    /**
     * Set the visibility of the item.
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Set the hidden status of the item.
     */
    public function hidden(bool|Closure $hidden = true): static
    {
        if ($hidden instanceof Closure) {
            return $this->condition(fn (...$args) => ! $hidden(...$args));
        }

        return $this->visible(! $hidden);
    }

    /**
     * Set the condition callback of the item.
     */
    public function condition(Closure $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Determine if the item should be rendered.
     */
    public function shouldRender(mixed ...$args): bool
    {
        return $this->visible && ($this->condition ? call_user_func($this->condition, ...$args) : true);
    }

    /**
     * Get the hidden status of the item.
     */
    public function isHidden(...$args): bool
    {
        return ! $this->shouldRender(...$args);
    }

    /**
     * Set the badge callable of the item.
     */
    public function badge(Closure $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Determine if the item is active.
     */
    public function isActive(): bool
    {
        // Early return if the route is not set.
        if (! isset($this->route)) {
            return false;
        }

        // Handle excat route match.
        if (request()->routeIs($this->route)) {
            return true;
        }

        // Handle wildcard route match.
        if (request()->routeIs(str_replace('.index', '.*', $this->route))) {
            return true;
        }

        return false;
    }
}
