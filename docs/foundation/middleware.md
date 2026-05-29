# Middleware

Redot Core ships three HTTP middleware that handle locale resolution, route-name based permission gating, and on-demand asset building. They are registered automatically by the package's custom application bootstrapper, so a consuming app rarely needs to wire them up by hand.

## Registration

All three middleware live under the `Redot\Http\Middleware` namespace and are appended/grouped inside `Redot\Application::configure()` (the replacement for the framework's default `bootstrap/app.php` setup). They are **not** registered via string aliases — they are referenced by their fully-qualified class names.

```php
$middleware->web(remove: [
    SubstituteBindings::class,
]);

$middleware->web(append: [
    Localization::class,
    SubstituteBindings::class,
    EnsureDependenciesBuilt::class,
]);

$middleware->group('dashboard', [
    RoutePermission::class,
    Locked::class . ':admins,dashboard.unlock',
]);
```

Key points:

- `Localization` and `EnsureDependenciesBuilt` run on every `web` request. `SubstituteBindings` is removed and re-appended **after** `Localization` so route-model binding resolves once the locale parameter has been stripped (see below).
- `RoutePermission` runs only inside the `dashboard` middleware group, which is applied to the dashboard route file: `Route::as('dashboard.')->prefix(...)->middleware('dashboard')->group(base_path('routes/dashboard.php'))`.

## Localization

`Redot\Http\Middleware\Localization` resolves the active locale per request, persists it to the session, and (when needed) redirects to the canonical locale-prefixed URL.

### What it does

```php
public function handle(Request $request, Closure $next): Response
```

1. **Determines the scope** — `dashboard` if the route matches `dashboard.*` or the path is `dashboard`, otherwise `website`.
2. **Picks the locale source list** from settings: `setting('dashboard_locales')` or `setting('website_locales')`. The first entry is the fallback.
3. **Resolves the locale** in priority order: `?locale=` query string → the `{locale}` route parameter → the session value (`dashboard_locale` / `website_locale`) → `Request::getPreferredLanguage()` against the allowed list.
4. If the resolved locale is empty or not in the allowed list, it falls back to the first configured locale.
5. **Persists & applies**: stores it in the session under `<scope>_locale`, calls `app()->setLocale()`, sets `URL::defaults(['locale' => $locale])`, and forgets the `locale` route parameter so it does not leak into route-model binding.
6. **Canonical redirect**: if the URL carried an explicit `{locale}` parameter that differs from the resolved locale, it issues a `301` redirect to the same path with the locale segment swapped (preserving the query string).

### Notes

- The `{locale}` URL prefix only exists when `config('redot.routing.append_locale_to_url')` is enabled; the bootstrapper adds a `{locale}` prefix constrained to `([a-zA-Z]{2})`.
- Because `URL::defaults(['locale' => ...])` is set, generated URLs automatically include the current locale without you passing it.
- The allowed locales come from the settings store, not config — see [Settings](/foundation/settings).

## RoutePermission

`Redot\Http\Middleware\RoutePermission` gates a request by its **route name**, treating the route name as a permission name.

```php
public function handle(Request $request, Closure $next): Response
{
    $name = $request->route()->getName();

    if (! $name || route_allowed($name)) {
        return $next($request);
    }

    abort(403);
}
```

- Unnamed routes pass through (there is no permission to check).
- Named routes are passed to the `route_allowed()` helper. If it returns `false`, the request is aborted with `403`.

### The `route_allowed()` / `url_allowed()` helpers

```php
function route_allowed(string $route, string $guard = 'admins'): bool
function url_allowed(string $url, string $guard = 'admins'): bool
```

`route_allowed()`:

- Returns `false` immediately if no user is authenticated on the `admins` guard.
- Caches the result forever per user under the key `permission.{guard}.{userId}.{route}`.
- Allows the route when **either** no `Permission` row named `$route` exists **or** `Gate::allows($route)` passes. In other words, routes with no matching permission record are open to authenticated admins; routes that have a permission record require that gate to pass.

`url_allowed()` resolves a URL to its route name (external URLs are always allowed) and delegates to `route_allowed()`. It is handy in Blade for conditionally rendering links.

The set of permission names is derived from routes guarded by `RoutePermission`: `SyncPermissionsCommand` scans all named `GET`/`DELETE` routes whose middleware stack contains `RoutePermission::class` and syncs them into permissions.

### Opting routes out

Routes that should be reachable by any authenticated admin (dashboard home, profile, utilities) drop the middleware with `withoutMiddleware()`. From the dashboard app's `routes/dashboard.php`:

```php
use Redot\Http\Middleware\RoutePermission;

Route::middleware('auth:admins')->group(function () {
    Route::get('/', DashboardController::class)
        ->name('index')
        ->withoutMiddleware(RoutePermission::class);

    Route::withoutMiddleware(RoutePermission::class)->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});
```

### Using the helpers in views and datatables

Gating UI on the same permission check keeps the dashboard consistent. From `app/Livewire/Datatables/Users.php`:

```php
Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit')),
Action::delete('dashboard.users.destroy')
    ->visible(route_allowed('dashboard.users.destroy'))
    ->condition(fn (User $user) => ! $user->trashed()),
```

And in Blade, from `resources/components/page-header.blade.php`:

```blade
@if ($create && url_allowed($create))
    {{-- render the "create" button --}}
@endif
```

See [Datatables](/packages/datatables/overview) for how actions consume these checks.

## EnsureDependenciesBuilt

`Redot\Http\Middleware\EnsureDependenciesBuilt` rebuilds front-end assets on demand by comparing source file modification times against a lock file.

```php
public function handle(Request $request, Closure $next): Response
```

- Looks for the lock file at `dist_path('lock.json')` — i.e. `public_path('assets/dist/lock.json')`.
- If the lock file is **missing**, it runs `Artisan::call('dependencies:build')` and continues.
- If the lock file **exists**, it reads its `files` and `directories` maps (each a `path => timestamp` pair). For every entry it resolves `base_path($path)` and runs `dependencies:build` if the path no longer exists or its `filemtime()` no longer matches the recorded timestamp.

### Notes

- This is effectively a dev-time auto-rebuild: any source change tracked in `lock.json` triggers a rebuild on the next request. The `dependencies:build` command writes the lock file.
- Because it can shell out to an Artisan command synchronously, it adds latency only when something is stale; matched timestamps short-circuit immediately.
- You can also build manually:

```bash
php artisan dependencies:build
```
