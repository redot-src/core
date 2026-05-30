# Sidebar

The Sidebar package lets you define the dashboard's navigation menu as a tree of items. You describe each entry — its title, icon, and where it links — and the package resolves the URLs, hides entries the current user can't access, and highlights the item that matches the current page.

## Quick start

Define your menu in `app/sidebar.php`, returning a `Sidebar` built from a list of items:

```php
<?php

use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

return Sidebar::make([
    Item::make()
        ->title(__('Dashboard'))
        ->icon('fa fa-home')
        ->route('dashboard.index'),

    Item::make()
        ->title(__('Website Management'))
        ->icon('fa fa-globe')
        ->children([
            Item::make()
                ->title(__('Users Management'))
                ->icon('fa fa-users')
                ->route('dashboard.users.index'),

            Item::make()
                ->title(__('Static Pages'))
                ->icon('fa fa-file-text')
                ->route('dashboard.static-pages.index'),
        ]),
]);
```

The dashboard layout reads this file each request, so any item the current user is not authorized to reach is hidden automatically, and the item matching the current route is marked active.

## Building items

Chain these onto `Item::make()`:

- **`title`** — the label shown in the menu.
- **`icon`** — an icon class string, e.g. `fa fa-home`.
- **`route`** — the named route to link to (with optional route parameters). Setting a route also enables permission filtering and active-state detection for the item.
- **`url`** — an explicit URL, used as-is. Use this for links that aren't named routes; it wins over `route` for the link itself.
- **`external`** — mark the link as external so it opens in a new tab.
- **`children`** — nest items one level deep to render the entry as a dropdown.
- **`hidden`** — hide the item. Pass `true` to always hide it, or a callback (receiving the current user) for conditional visibility.

## Examples

### A top-level link

```php
Item::make()->title(__('Dashboard'))->icon('fa fa-home')->route('dashboard.index');
```

### A dropdown group

Items with `children` render as a dropdown. A group disappears entirely if all of its children are hidden or filtered out:

```php
Item::make()
    ->title(__('Utilities'))
    ->icon('fa fa-clipboard')
    ->children([
        Item::make()
            ->title(__('Shortened URLs'))
            ->icon('fa fa-link')
            ->route('dashboard.shortened-urls.index'),
    ]);
```

### Conditionally hiding an item

Pass a boolean to hide based on config, or a callback for per-user logic:

```php
Item::make()
    ->title(__('Shortened URLs'))
    ->route('dashboard.shortened-urls.index')
    ->hidden(config('redot.features.website.enabled') === false);
```

### An external link

```php
Item::make()->title(__('Docs'))->url('https://example.com/docs')->external(true);
```

## Notes

- An item is hidden automatically when the current user isn't allowed to reach its `route`. Items defined with only a `url` are not permission-filtered — gate those yourself with `hidden`.
- Active highlighting follows the matched route: an `*.index` route also stays active on that section's create/edit pages, and an active child bubbles its active state up to its parent.

## Related

- [Helpers](/foundation/helpers) — `route_allowed()` and related permission helpers.
- [Components overview](/components/overview)
