# Datatable Actions

Actions are the per-row controls rendered in the trailing "actions" column of a [Datatable](/packages/datatables/overview) — view, edit, delete, and any custom buttons you need. The `Redot\Datatables\Actions\Action` class describes a single action, and `Redot\Datatables\Actions\ActionGroup` collapses several actions into a dropdown.

## Key concepts

- An action is a fluent builder. You start with a factory (`Action::make()`, `Action::edit()`, …) and chain configuration methods, each returning `$this`.
- An action can be a **link/form** (driven by a `route()` or `href()`) or an **inline action** (driven by an `action()` callback that runs server-side). The two are mutually exclusive.
- Actions are returned from the `actions()` method of a Livewire datatable class. They are rendered as `<a>` elements whose attributes are computed per row via the `BuildAttributes` trait.
- Both `Action` and `ActionGroup` use `Illuminate\Support\Traits\Macroable`, so you can register custom factory/builder methods at runtime.

## Creating actions

### Factories

```php
use Redot\Datatables\Actions\Action;

Action::make(?string $label = null, ?string $icon = null): static
```

There are pre-configured factories for the common CRUD operations. Each accepts an optional route name and route parameters, and sets a sensible label (translated via `datatables::datatable.actions.*`), icon, HTTP method, and confirmation default:

| Factory | Icon | Method | Notes |
| --- | --- | --- | --- |
| `Action::view($route, $parameters)` | `fas fa-eye` | get | opens in fancybox by default |
| `Action::edit($route, $parameters)` | `fas fa-edit` | get | |
| `Action::delete($route, $parameters)` | `fas fa-trash-alt` | delete | confirmable by default |
| `Action::restore($route, $parameters)` | `fas fa-trash-restore` | post | confirmable by default |
| `Action::export($route, $parameters)` | `fas fa-file-export` | get | |

The `$route`/`$parameters` arguments are optional — if you omit them you can call `->route()` later, or use `->href()` / `->action()` instead.

### Builder methods

Signatures of the public surface on `Action`:

```php
->label(string $label): static
->icon(string $icon): static
->route(string $route, array $parameters = [], ?string $method = null, ?bool $bounded = null): static
->href(string|Closure $href): static
->parameters(array $parameters): static            // replaces parameters
->bounded(bool $bounded = true): static            // push row as first route param
->body(array $body = []): static                   // merged form/query payload
->method(string $method): static                   // get|post|put|patch|delete
->visible(bool $visible = true): static
->hidden(bool $hidden = true): static
->grouped(bool $grouped = true): static
->expanded(bool $expanded = true): static
->condition(Closure $condition): static            // fn (Model $row): bool
->action(string $name, Closure $callback): static  // inline action
->success(Closure $callback): static
->failure(Closure $callback): static
->newTab(bool $newTab = true): static
->fancybox(bool $fancybox = true): static
->confirmable(bool $confirmable = true, ?string $message = null): static
->confirmMessage(string $confirmMessage): static
```

The `BuildAttributes` trait also adds `->class($class)`, `->css($css)`, `->attributes(array)`, and `->attribute($key, $value)` for arbitrary HTML attributes on the rendered element.

## Routes, parameters and bound rows

`route()` builds the final `href` with Laravel's `route()` helper. By default `bounded` is `true`, which **prepends the current row** to the route parameters so the model binds via `SubstituteBindings` (e.g. `dashboard.users.edit` resolves `{user}` from the row). Set `bounded: false` when the route should not receive the row as its first parameter.

Both `parameters` and `body` values are evaluated per row — a closure receives the row model and its return value is used. For non-GET methods, `body` is base64/JSON-encoded into a `request-body` attribute; for GET it is appended to the query string.

```php
Action::make(__('Impersonate'), 'fas fa-user-secret')
    ->visible(route_allowed('dashboard.impersonate-users.create'))
    ->condition(fn (User $user) => ! $user->trashed())
    ->route('dashboard.impersonate-users.store', method: 'post', bounded: false)
    ->body(['user_id' => fn (User $user) => $user->id])
    ->confirmable(message: __('Are you sure you want to impersonate this user?')),
```

Use `href()` instead of `route()` when you want a raw URL (string or a closure receiving the row).

## Visibility vs. condition

There are two ways to hide an action, and they serve different purposes:

- `visible(bool)` / `hidden(bool)` — a static flag, typically tied to authorization. In the consumer this is paired with the `route_allowed()` helper so an action only renders if the current user may hit that route.
- `condition(Closure)` — a per-row callback `fn (Model $row): bool`. Use it for row-dependent logic (e.g. only show *restore* on trashed rows).

`shouldRender(Model $row)` returns `true` only when `visible` is `true` **and** the condition (if any) passes.

```php
Action::delete('dashboard.users.destroy')
    ->visible(route_allowed('dashboard.users.destroy'))
    ->condition(fn (User $user) => ! $user->trashed()),
Action::restore('dashboard.users.restore')
    ->visible(route_allowed('dashboard.users.restore'))
    ->condition(fn (User $user) => $user->trashed()),
```

## Confirmation

`confirmable()` requires the user to confirm before the action fires. The confirm prompt defaults to `datatables::datatable.actions.confirm`, or a custom string via the `$message` argument / `confirmMessage()`.

Gotcha: a confirmable action **must not** use the `get` method (unless it is an inline `action()`); otherwise `prepareAttributes()` throws an `InvalidArgumentException`. This is why `Action::delete()` ships as `method('delete')` and `Action::restore()` as `method('post')`.

## Display behavior

- `fancybox()` renders the link as a Fancybox iframe (`data-fancybox`, `data-type="iframe"`). `Action::view()` enables this by default — disable it with `->fancybox(false)`.
- `newTab()` adds `target="_blank"`.
- When an action has a `label` and is **not** grouped or expanded, the label becomes a Bootstrap tooltip (`title` + `data-bs-toggle="tooltip"`); the button renders icon-only.
- `expanded()` shows the label text inline next to the icon instead of as a tooltip.
- `grouped()` (set automatically inside an `ActionGroup`) renders the action as a `dropdown-item`.

```php
// Open in a new tab instead of fancybox, label shown as tooltip
Action::view('website.static-pages.show')->fancybox(false)->newTab(),
```

## Inline actions

Instead of navigating, an action can run a server-side closure via `action(string $name, Closure $callback)`. The rendered link gets `href="#"`, an `action-name`, and an `action-key` (the row's key) so the datatable can dispatch back to the component. `success()` and `failure()` register follow-up callbacks.

Constraints enforced in `prepareAttributes()`:

- An inline action **cannot** also define a `route` or `href` — combining them throws `InvalidArgumentException`.
- The `name` passed to `action()` must be non-empty.

## ActionGroup

`ActionGroup` collapses several actions into a Bootstrap dropdown. Calling `actions()` or `add()` marks each child action as `grouped(true)`.

```php
use Redot\Datatables\Actions\ActionGroup;

ActionGroup::make(?string $label = null, ?string $icon = null): static
    ->actions(array $actions): static   // replaces, marks children grouped
    ->add(Action $action): static       // appends, marks child grouped
    ->visible(bool $visible = true): static
    ->hidden(bool $hidden = true): static
    ->condition(Closure $condition): static
```

A group renders only when it is visible, its condition passes, **and** at least one child action would render for the row (`shouldRender()`). The toggle is a `dropdown-toggle` button (icon-only when there is no label) with a per-row `wire:key`.

### `Datatable::defaultActionGroup()`

Most consumer datatables don't build groups by hand — they pass a flat list to `Datatable::defaultActionGroup()`, which keeps the first couple of actions inline and folds the rest into an `ActionGroup` (using the `fas fa-ellipsis-v` icon by default). On mobile (`is_mobile()`) all actions are grouped.

```php
public static function defaultActionGroup(array $actions, ?string $label = null, ?string $icon = null): array
```

## Usage

A complete `actions()` method from the consumer app (`app/Livewire/Datatables/Users.php`):

```php
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Datatable;

/**
 * Get the actions for the datatable.
 */
public function actions(): array
{
    return Datatable::defaultActionGroup([
        Action::view('dashboard.users.show')->visible(route_allowed('dashboard.users.show')),
        Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit')),
        Action::make(__('Impersonate'), 'fas fa-user-secret')
            ->visible(route_allowed('dashboard.impersonate-users.create'))
            ->condition(fn (User $user) => ! $user->trashed())
            ->route('dashboard.impersonate-users.store', method: 'post', bounded: false)
            ->body(['user_id' => fn (User $user) => $user->id])
            ->confirmable(message: __('Are you sure you want to impersonate this user?')),
        Action::delete('dashboard.users.destroy')
            ->visible(route_allowed('dashboard.users.destroy'))
            ->condition(fn (User $user) => ! $user->trashed()),
        Action::restore('dashboard.users.restore')
            ->visible(route_allowed('dashboard.users.restore'))
            ->condition(fn (User $user) => $user->trashed()),
    ]);
}
```

A simpler list (`app/Livewire/Datatables/ShortenedUrls.php`) — a custom action plus the standard edit/delete:

```php
public function actions(): array
{
    return Datatable::defaultActionGroup([
        Action::make(__('Analytics'), 'fas fa-chart-line')
            ->route('dashboard.shortened-urls.analytics'),
        Action::edit('dashboard.shortened-urls.edit'),
        Action::delete('dashboard.shortened-urls.destroy'),
    ]);
}
```

## Gotchas

- Confirmable + `get` (non-inline) throws `InvalidArgumentException`. Use a non-GET method.
- Inline `action()` cannot be combined with `route()`/`href()`.
- `method()` only accepts `get`, `post`, `put`, `patch`, `delete` (case-insensitive); anything else throws `InvalidArgumentException`.
- `route()` parameters are *merged* (it calls `array_merge`), while `parameters()` *replaces* them.
- `body()` is merged on each call.
- Remember `bounded` defaults to `true` — pass `bounded: false` for routes that should not receive the row model.

## See also

- [Datatables overview](/packages/datatables/overview)
