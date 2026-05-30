# Middleware

Redot Core applies three HTTP behaviors to every dashboard request: it resolves
the active locale, gates dashboard routes by permission, and rebuilds front-end
assets when sources change. These run automatically — a consuming app does not
wire them up — so this page covers how the behavior affects requests and how to
work with it.

## Locale resolution

Every web request resolves a locale (from `?locale=`, the URL segment, the
session, or the browser), applies it, remembers it in the session, and makes
generated URLs carry it automatically — so `route()` calls do not need a
`locale` argument. If the URL carried a different locale than the one resolved,
the request is redirected to the corrected URL. The allowed locales come from
the `dashboard_locales` / `website_locales` settings, so changing them takes
effect immediately. See [Localization](/foundation/localization).

## Route permission gating

Dashboard routes are gated by their **route name**, treated as a permission
name. A request is allowed when the current admin passes the gate for that route
name; otherwise it is aborted with `403`. Routes with no matching permission
record are open to any authenticated admin, and unnamed routes always pass.

Gate the matching UI on the same check so the dashboard stays consistent — use
the [`route_allowed()` / `url_allowed()`](/foundation/helpers) helpers:

```php
Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit'));
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

The set of permission names is derived from the gated routes. Sync them with:

```bash
php artisan permissions:sync
```

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
