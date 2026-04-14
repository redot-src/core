<?php

namespace Redot\Sidebar;

use Closure;

class Item
{
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
     * The hidden status of the item.
     */
    public bool|Closure $hidden = false;

    /**
     * Determine if the item is active.
     */
    public bool $active = false;

    /**
     * Create a new item instance.
     */
    public static function make(): static
    {
        return new static;
    }

    /**
     * Set the title of the item.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the icon of the item.
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set the route of the item.
     */
    public function route(string $route, array $parameters = []): self
    {
        $this->route = $route;
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Set the URL of the item.
     */
    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the external status of the item.
     */
    public function external(bool $external): self
    {
        $this->external = $external;

        return $this;
    }

    /**
     * Set the children of the item.
     */
    public function children(array $children): self
    {
        $this->children = $children;

        // Assign the parent to each child.
        foreach ($this->children as $child) {
            $child->parent = $this;
        }

        return $this;
    }

    /**
     * Set the hidden status of the item.
     */
    public function hidden(bool|Closure $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get the hidden status of the item.
     */
    public function isHidden(...$args): bool
    {
        if (is_callable($this->hidden)) {
            return call_user_func($this->hidden, ...$args);
        }

        return $this->hidden;
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
