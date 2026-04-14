<?php

namespace Redot\Sidebar;

use Illuminate\Support\Arr;

class Sidebar
{
    /**
     * The items of the sidebar.
     */
    public array $items = [];

    /**
     * Active items.
     */
    public array $activeItems = [];

    /**
     * Create a new sidebar instance.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new sidebar instance statically.
     */
    public static function make(array $items = []): static
    {
        return new static($items);
    }

    /**
     * Add an item to the sidebar.
     */
    public function item(Item $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple items to the sidebar.
     */
    public function items(array $items): static
    {
        foreach ($items as $item) {
            $this->item($item);
        }

        return $this;
    }

    /**
     * Get the items of the sidebar.
     */
    public function getItems(): array
    {
        $items = [];

        foreach ($this->items as $item) {
            $item = $this->prepareItem($item);

            if ($item !== false) {
                $items[] = $item;
            }
        }

        foreach ($this->activeItems as &$current) {
            foreach ($this->activeItems as $other) {
                if ($current === $other) {
                    continue;
                }

                if (request()->routeIs($other->route)) {
                    $current->active = false;
                }

                if (strlen($other->route) > strlen($current->route)) {
                    $current->active = false;
                }
            }
        }

        // Reset the active items.
        $this->activeItems = array_filter($this->activeItems, fn ($item) => $item->active);

        // Add active to the active item parent
        foreach ($this->activeItems as $item) {
            if (isset($item->parent)) {
                $item->parent->active = true;
            }
        }

        return $items;
    }

    /**
     * Prepare an item for display.
     */
    protected function prepareItem(Item $item)
    {
        if ($item->route && ! route_allowed($item->route)) {
            return false;
        }

        if (is_callable($item->hidden) ? call_user_func($item->hidden, current_admin()) : $item->hidden) {
            return false;
        }

        if (! $item->url) {
            $item->url = $item->route ? route($item->route, $item->parameters) : '#';
        }

        // Set the active status of the item.
        $item->active = $item->isActive();

        if ($item->active) {
            $this->activeItems[] = $item;
        }

        if (count($item->children) > 0) {
            // Recursively prepare each child item.
            $item->children = array_map(fn ($child) => $this->prepareItem($child), $item->children);

            // Filter out any false values from the children array.
            $item->children = array_filter($item->children, fn ($child) => $child !== false);
            $item->children = array_values($item->children);

            if (count($item->children) === 0) {
                return false;
            }
        }

        return $item;
    }

    /**
     * Get the active item.
     */
    public function getActiveItem(): ?Item
    {
        return Arr::first($this->activeItems);
    }
}
