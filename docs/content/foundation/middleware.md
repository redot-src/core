# Middleware

Redot Core applies three HTTP behaviors to every dashboard request: it resolves
the active locale, gates dashboard routes by permission, and rebuilds front-end
assets when sources change. These run automatically — a consuming app does not
wire them up — so this page covers how the behavior affects requests and how to
work with it.

## Locale resolution

Website and dashboard routes resolve a locale for the scope passed to the
middleware (`website` or `dashboard`). It is picked from `?locale=`, the URL
segment, the session, a locale cookie, or the browser, then remembered in the
session and a long-lived cookie. Generated URLs carry it automatically — so
`route()` calls do not need a `locale` argument. If the URL carried a different
locale than the one resolved, the request is redirected to the corrected URL.
The allowed locales come from the `dashboard_locales` / `website_locales`
settings, so changing them takes effect immediately. See
[Localization](/foundation/localization).

## Route permission gating

Dashboard routes — web and API alike — are gated by their **route name**,
treated as a permission name. A request is allowed when the current admin
passes the gate for that route name; otherwise it is aborted with `403`. Named
routes are denied when no gate grants the resolved permission, while unnamed
routes always pass. The gate authenticates against the guard of the route's
`auth` middleware (falling back to `admins`), so dashboard API routes are
checked against the admin resolved by their API guard.

Dashboard API routes should alias their permission to the matching web route
with `usePermission()` (e.g. `usePermission('dashboard.admins.create')`), so
one permission covers both surfaces instead of a duplicated `api.dashboard.*`
name. Synced permissions are stored under the guard's provider's first
configured guard, so guards sharing a provider (session + API) share one
permission row.

Conventional form and mutation routes share a permission automatically:

- `*.create` and `*.store` use the `*.create` permission.
- `*.edit` and `*.update` use the `*.edit` permission.

For a custom pair that cannot be inferred from its final route-name segment,
set the shared permission explicitly with `usePermission()`:

```php
Route::post('users/{user}/suspend', [UserController::class, 'suspend'])
    ->name('users.suspend.store')
    ->usePermission('users.suspend');
```

Resource routes accept action-specific overrides with `usePermissions()`. Any
action omitted from the map keeps its conventional permission:

```php
Route::resource('users', UserController::class)->usePermissions([
    'store' => 'users.onboard',
    'destroy' => 'users.archive',
]);
```

Gate the matching UI on the same check so the dashboard stays consistent — use
the [`route_allowed()` / `url_allowed()`](/foundation/helpers) helpers:

```php
Action::edit('posts.edit')->visible(route_allowed('posts.edit'));
```

```blade
@if ($create && url_allowed($create))
    {{-- render the "create" button --}}
@endif
```

### Opting a route out

Routes that any authenticated admin should reach (dashboard home, profile,
utilities) drop the gate with `withoutMiddleware()`:

```php
use Redot\Http\Middleware\RoutePermission;

Route::get('profile', [ProfileController::class, 'edit'])
    ->name('profile.edit')
    ->withoutMiddleware(RoutePermission::class);
```

The set of resolved permission names is derived from every gated route,
including write routes. Sync them with:

```bash
php artisan permissions:sync
```

Pass `--prune` to also remove previously discovered permissions whose routes no
longer exist; permissions created by hand are never pruned. See
[Commands](/commands/overview) for details.

### Local development bypass

While developing locally you can skip syncing and assigning permissions by
setting `redot.permissions.bypass`. When
enabled in the `local` environment, every Gate check — including
`RoutePermission` and `route_allowed()` — passes. The bypass is never honored
outside `local`. See [Configuration](/architecture/configuration#permissions).

## On-demand asset building

On each web request the framework checks whether tracked front-end sources have
changed and rebuilds them if so, so you do not have to run a watcher in
development. A matched, up-to-date build short-circuits immediately and adds no
latency. You can also build manually:

```bash
php artisan dependencies:build
```

See the [Asset & Init System](/frontend/asset-system) for the bigger picture.

## Related

- [Localization](/foundation/localization) — locale resolution and URL strategy.
- [Helpers](/foundation/helpers) — `route_allowed()`, `url_allowed()`.
- [Datatables](/packages/datatables/overview) — actions that consume the gate.
- [Asset & Init System](/frontend/asset-system) — the build step.
