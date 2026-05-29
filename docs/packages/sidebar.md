# Sidebar

The Sidebar package provides a small fluent builder for defining the dashboard navigation menu. You declare a tree of `Item` objects, and the `Sidebar` resolves their URLs, filters them by permission and visibility, and computes which item is currently active based on the matched route.

## Key concepts

- **`Redot\Sidebar\Sidebar`** — the container that holds the top-level items. It is bound in the container as a singleton (and aliased as `sidebar`), but in practice the dashboard builds a fresh instance from a config-style PHP file per request (see [Usage](#usage)).
- **`Redot\Sidebar\Item`** — a fluent builder for a single menu entry. Items can nest one level deep via `children()` to render as dropdowns.
- **Permission filtering** — items with a `route` are hidden automatically when the current user is not allowed to access that route, via the package's `route_allowed()` helper.
- **Active detection** — the sidebar marks the item matching the current route as active, propagates active state to the parent of a nested item, and resolves conflicts so only the most specific match stays active.

## Container binding

`RedotServiceProvider::register()` registers the singleton and alias:

```php
$this->app->singleton(Sidebar::class, fn () => new Sidebar);
$this->app->alias(Sidebar::class, 'sidebar');
```

Resolving `app(Sidebar::class)` or `app('sidebar')` gives you an empty `Sidebar` you can populate. The dashboard layout, however, ignores the singleton and `include`s `app/sidebar.php` directly, which returns its own configured `Sidebar` instance.

## Sidebar

```php
namespace Redot\Sidebar;

class Sidebar
{
    public array $items = [];
    public array $activeItems = [];
    public string $guard = 'admins';

    public function __construct(array $items = [], string $guard = 'admins');
    public static function make(array $items = [], string $guard = 'admins'): static;

    public function item(Item $item): static;
    public function items(array $items): static;

    public function getItems(): array;
    public function getActiveItem(): ?Item;
}
```

- **`make(array $items = [], string $guard = 'admins')`** — static constructor. Pass the full item tree up front, or start empty and add items later.
- **`item(Item $item)` / `items(array $items)`** — append a single item or an array of items. Both return `$this` for chaining.
- **`$guard`** — the auth guard used to resolve the current user when evaluating `hidden` callbacks. Defaults to `admins`.
- **`getItems(): array`** — the main entry point. It prepares every item (resolving URLs, filtering by permission and visibility, computing active state) and returns the visible top-level items. Items that are filtered out are excluded entirely.
- **`getActiveItem(): ?Item`** — returns the first active item, or `null`. Call this only after `getItems()`, since `getItems()` populates the active-item list.

### How `getItems()` prepares items

For each item (recursively for children):

1. If the item has a `route` and `route_allowed($route)` returns `false`, the item is dropped.
2. The `hidden` value is evaluated — if it is a closure it is called with the authenticated user (`auth($guard)->user()`); if the result is truthy, the item is dropped.
3. If no explicit `url` was set, the URL is generated from `route($route, $parameters)`, or falls back to `'#'` when there is no route.
4. The item's active state is computed via `Item::isActive()`.
5. Children are prepared the same way; a parent whose children all get filtered out is itself dropped.

After preparation, `getItems()` reconciles active state: when multiple items match, the longer (more specific) route wins, and the active state of a nested child is bubbled up to its `parent`.

## Item

```php
namespace Redot\Sidebar;

class Item
{
    public ?Item $parent = null;
    public ?string $title = null;
    public ?string $icon = null;
    public ?string $route = null;
    public ?string $url = null;
    public bool $external = false;
    public array $parameters = [];
    public array $children = [];
    public bool|Closure $hidden = false;
    public bool $active = false;

    public static function make(): static;

    public function title(string $title): static;
    public function icon(string $icon): static;
    public function route(string $route, array $parameters = []): static;
    public function url(string $url): static;
    public function external(bool $external): static;
    public function children(array $children): static;
    public function hidden(bool|Closure $hidden): static;

    public function isHidden(...$args): bool;
    public function isActive(): bool;
}
```

- **`title(string)`** — the label shown in the menu.
- **`icon(string)`** — an icon class string (e.g. `'fa fa-home'`). The dashboard renders it via its `<x-icon>` component or directly as an `<i class="...">`.
- **`route(string $route, array $parameters = [])`** — the named route the item links to. Parameters are passed to `route()` when generating the URL. Setting a route also enables permission filtering and active detection.
- **`url(string)`** — an explicit URL, used as-is. When set, it overrides route-based URL generation.
- **`external(bool)`** — marks the link as external (the dashboard opens these in a new tab via `target="_blank"`).
- **`children(array $children)`** — nested items, rendered as a dropdown. Each child's `parent` is automatically assigned to the current item.
- **`hidden(bool|Closure)`** — hide the item. A boolean hides unconditionally; a closure receives the authenticated user and should return a boolean.

### Active detection (`isActive()`)

An item with a route is active when:

- the current request exactly matches the route (`request()->routeIs($route)`), or
- the current request matches the wildcard form, where a trailing `.index` is replaced with `.*` (so `dashboard.users.index` also activates for `dashboard.users.create`, `dashboard.users.edit`, etc.).

Items without a route are never active on their own, but a parent becomes active when one of its children is active.

## Usage

The dashboard defines its menu in `app/sidebar.php`, which returns a configured `Sidebar`. This is the canonical, real-world example:

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
                ->route('dashboard.users.index')
                ->icon('fa fa-users'),

            Item::make()
                ->title(__('Static Pages'))
                ->route('dashboard.static-pages.index')
                ->icon('fa fa-file-text'),
        ]),

    Item::make()
        ->title(__('Utilities'))
        ->icon('fa fa-clipboard')
        ->children([
            Item::make()
                ->title(__('Shortened URLs'))
                ->route('dashboard.shortened-urls.index')
                ->hidden(config('redot.features.website.enabled') === false)
                ->icon('fa fa-link'),
            // ...
        ]),
]);
```

The layout component then resolves the items and active entry per request:

```php
namespace App\View\Layouts;

use Illuminate\View\Component;
use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

class Dashboard extends Component
{
    public Sidebar $sidebar;
    public array $items = [];
    public ?Item $active = null;

    public function render()
    {
        $this->sidebar = include base_path('app/sidebar.php');

        $this->items = $this->sidebar->getItems();
        $this->active = $this->sidebar->getActiveItem();

        // Fall back to the active item's title for the page title.
        if ($this->title === null && $this->active && $this->active->title) {
            $this->title = $this->active->title;
        }

        return view('layouts.dashboard.base');
    }
}
```

Rendering the prepared items in Blade is a simple loop — items with children become dropdowns:

```blade
@foreach ($items as $item)
    @if (isset($item->children) && count($item->children) > 0)
        @include('layouts.dashboard.partials.sidebar.dropdown', ['item' => $item])
    @else
        @include('layouts.dashboard.partials.sidebar.item', ['item' => $item])
    @endif
@endforeach
```

Each item exposes `$item->url`, `$item->title`, `$item->icon`, `$item->active`, `$item->external`, and `$item->children` for use in templates.

## Gotchas

- **Call `getItems()` before `getActiveItem()`.** Active items are collected during `getItems()`; calling `getActiveItem()` first returns `null`.
- **One level of nesting.** The structure supports `children`, but the dashboard treats children as leaf entries in a dropdown — children of children are not rendered.
- **Permission filtering is route-driven.** An item is permission-filtered only if it has a `route`. The check uses `route_allowed($route)`, which requires an authenticated user on the guard and consults Laravel's gate / the `Permission` records (results are cached forever per user+route). Items defined purely with `url()` are not permission-filtered.
- **Hidden closures receive the user.** When `hidden` is a closure, it is invoked with `auth($guard)->user()` — be ready for a `null` user if the guard is not authenticated.
- **`url()` wins over `route()` for the link.** If both are set, the explicit URL is kept; the route is still used for permission and active checks.
- **Empty parents disappear.** A parent whose visible children all get filtered out is removed from the menu.

See also [Helpers](/foundation/helpers) for `route_allowed()` and related permission helpers.
