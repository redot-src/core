# Auth Overview

The Redot Auth package (`Redot\Auth`) provides a declarative, guard-aware way to register a complete authentication stack — login, registration, password reset, magic links, email verification, logout, and a lock screen — with a single call. Instead of scaffolding controllers and routes per app, you describe a guard and its views, and the package wires the routes and actions for you.

## Architecture

The package is built around four moving parts:

- **`RedotAuth` facade** (`Redot\Auth\Facades\RedotAuth`) — the public entry point. Its only documented method is `routes(...)`.
- **`RedotAuthManager`** (`Redot\Auth\RedotAuthManager`) — resolves the guard configuration into an `AuthContext` and runs each feature's route registrar.
- **`AuthContext`** — an immutable value object describing the resolved guard (model, provider, broker, views, middleware, enabled features). It is passed to every registrar and action.
- **Route registrars** (`Redot\Auth\Routes\*`) — one class per feature, each implementing `Redot\Auth\Contracts\RouteRegistrar`. They define the actual routes and delegate behavior to action classes in `Redot\Auth\Actions\*`.

The service provider `RedotAuthServiceProvider` registers `RedotAuthManager` as a singleton and aliases the facade as `RedotAuth`. There is no config file to publish — everything is driven by your existing `config/auth.php` guards/providers plus the arguments you pass to `RedotAuth::routes()`.

## The `RedotAuth::routes()` method

```php
RedotAuth::routes(
    string $guard,
    ?Closure $scope = null,
    array $views = [],
    array $disable = [],
    array $registrars = [],
    mixed $home = null,
): void
```

- **`$guard`** — the name of a guard defined in `config/auth.php` (e.g. `admins`, `users`, `users-api`). The manager reads `auth.guards.{guard}`, its `provider`, and the provider's `model`. If the guard, provider, or model is missing/invalid, an `InvalidArgumentException` is thrown.
- **`$scope`** — an optional `Closure` receiving an Eloquent `Builder` used to constrain which users can authenticate. It is applied during user lookup (see [QueriesUsers](#queriesusers)).
- **`$views`** — a map of feature keys to Blade view names (`login`, `forgot-password`, `reset-password`, `magic-link`, `magic-link-code`, `unlock`, `verify-email`, ...). Each registered view receives the `AuthContext` as `$context`.
- **`$disable`** — an array of feature keys to skip. Supported values: `register`, `magic-link`, `email-verification`, `logout`, `lock-screen`.
- **`$registrars`** — an optional override map of `feature => RouteRegistrar::class` to replace the default registrar for a feature.
- **`$home`** — where to redirect after login. May be a route name (string), a callable returning a URL, or `null` (defaults to the `index` route under the current name prefix).

### API vs. web guards

The manager inspects the guard's `driver`. If it is one of `sanctum`, `passport`, `jwt`, or `api`, the context is flagged as **API mode** (`$context->api === true`). In API mode the registrars skip the `GET` view routes (only the JSON-posting routes are registered) and the `Login` action issues a Sanctum-style token (`$user->createToken('auth_token')`) instead of starting a session. The lock screen is also force-disabled for API guards.

### Name prefixing

`RedotAuthManager` reads the current router group stack and uses the trailing `as` prefix as the route-name prefix. This means you should call `RedotAuth::routes()` inside the same group that sets your `name(...)` prefix, so generated routes (e.g. `login`, `login.store`) inherit it.

## AuthContext

`AuthContext` is the resolved description of the auth setup. Useful members for consumers and custom registrars/actions:

- Readonly properties: `guard`, `provider`, `broker`, `model`, `scope`, `api`, `namePrefix`, `views`, `home`, `identifiers`, and the computed `disabled` map.
- `routeName(string $name): string` — prepends the name prefix.
- `featureEnabled(string $feature): bool` — whether a feature survived the `disable` list.
- `homeUrl(): string` — resolves `$home` (callable / route name / default `index`).
- `identifierInputName(): string` — returns the single identifier column when there is one, otherwise `'identifier'`.
- `guest(): array` / `auth(): array` — the middleware stacks (`guest:{guard}` and `auth:{guard}`, plus the `Locked` middleware when the lock screen is enabled).
- `lockedMiddleware(): string` — the `Locked` middleware string bound to the guard and the `unlock` route.

The lock screen is automatically disabled when the guard is API-based or when no `unlock` view is provided.

## Concerns

Two traits carry the shared logic used by the action classes; you can reuse them in custom actions.

### QueriesUsers

`Redot\Auth\Concerns\QueriesUsers` resolves and updates the authenticating user:

- `findUserByIdentifier(string $value, AuthContext $context): ?Model` — queries `$context->model`, applies the optional `scope`, and matches `$value` against any of `$context->identifiers` via `orWhere`.
- `applyScope(Builder $query, ?Closure $scope): Builder` — applies the scope closure if present.
- `touchLastLoginAt($user): void` — best-effort `update(['last_login_at' => now()])`; silently ignored if the model has no such column.

### RateLimitsRequests

`Redot\Auth\Concerns\RateLimitsRequests` provides throttling keyed by identifier + IP:

- `throttleKey(Request $request, AuthContext $context, string $prefix = ''): string`
- `ensureNotRateLimited(Request $request, AuthContext $context, string $prefix = '', int $attempts = 5, bool $dispatch = true): void` — throws a `ValidationException` (message `auth.throttle`) and dispatches `Illuminate\Auth\Events\Lockout` once the attempt limit is exceeded.

The `Login` action uses both: it validates, calls `ensureNotRateLimited`, looks the user up, checks the password with `Hash::check`, hits/clears the rate limiter accordingly, and touches `last_login_at` on success.

## Usage

Web dashboard guard (from the consumer app's `routes/dashboard.php`), registered without the route-permission middleware and with a scope restricting login to active admins:

```php
use Illuminate\Support\Facades\Route;
use Redot\Auth\Facades\RedotAuth;

Route::withoutMiddleware(RoutePermission::class)->group(function () {
    RedotAuth::routes(
        guard: 'admins',
        scope: fn ($query) => $query->where('active', true),
        views: [
            'login' => 'dashboard.auth.login',
            'forgot-password' => 'dashboard.auth.forgot-password',
            'reset-password' => 'dashboard.auth.reset-password',
            'magic-link' => 'dashboard.auth.magic-link',
            'magic-link-code' => 'dashboard.auth.magic-link-code',
            'unlock' => 'dashboard.auth.unlock',
        ],
        disable: [
            'register',
            'email-verification',
        ],
    );
});
```

Public website guard (`routes/website.php`) with registration and email verification enabled:

```php
RedotAuth::routes(
    guard: 'users',
    views: [
        'login' => 'website.auth.login',
        'register' => 'website.auth.register',
        'forgot-password' => 'website.auth.forgot-password',
        'reset-password' => 'website.auth.reset-password',
        'magic-link' => 'website.auth.magic-link',
        'magic-link-code' => 'website.auth.magic-link-code',
        'verify-email' => 'website.auth.verify-email',
    ],
);
```

API guard (`routes/api/website.php` and `routes/api/dashboard.php`). With an API driver, no views are needed — only JSON endpoints are registered:

```php
RedotAuth::routes(guard: 'users-api');

Route::prefix('auth')->group(function () {
    RedotAuth::routes(
        guard: 'admins-api',
        scope: fn ($query) => $query->where('active', true),
        disable: ['register', 'email-verification'],
    );
});
```

### Customizing login identifiers and rules

The `Login` action supports configuring per-provider identifiers and validation rules (static, so set them in a service provider boot):

```php
use Redot\Auth\Actions\Login;

Login::identifiers('users', ['email', 'username']);
Login::validationRules('users', [
    'identifier' => ['required', 'string'],
    'password' => ['required'],
]);
```

When more than one identifier is configured, the request input name becomes `identifier`; with a single identifier it stays as that column name (e.g. `email`).

## Gotchas

- There is no publishable config; the package relies on `config/auth.php` and the call-site arguments.
- Call `RedotAuth::routes()` inside the route group whose `name(...)` prefix you want generated routes to inherit — the prefix is read from the router's group stack at call time.
- A guard with an API driver (`sanctum`/`passport`/`jwt`/`api`) automatically switches to token responses, drops view routes, and disables the lock screen.
- The lock screen requires an `unlock` view; without it the feature is silently disabled even if not listed in `disable`.
- `last_login_at` updates are best-effort and ignored if the column does not exist.
