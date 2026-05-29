# Auth Routes

The auth package ships every authentication endpoint (login, logout, registration, magic links, password reset, email verification, lock screen) as a small **route registrar** class. Calling `RedotAuth::routes()` resolves a shared [`AuthContext`](#) and runs the enabled registrars, so a single call registers a full auth surface for a guard.

## Concepts

### The `RouteRegistrar` contract

Every feature's routes live behind one interface:

```php
namespace Redot\Auth\Contracts;

use Redot\Auth\AuthContext;

interface RouteRegistrar
{
    public function register(AuthContext $context): void;
}
```

A registrar receives the resolved `AuthContext` and is responsible for declaring its own routes (URIs, methods, names, middleware). Registrars are resolved from the container with `app($class)`, so they can take constructor dependencies.

### How registrars are enabled

`Redot\Auth\RedotAuthManager` holds a fixed map of feature key to default registrar class:

```php
protected const REGISTRARS = [
    'login'              => LoginRoutes::class,
    'register'           => RegistrationRoutes::class,
    'password-reset'     => PasswordResetRoutes::class,
    'magic-link'         => MagicLinkRoutes::class,
    'email-verification' => EmailVerificationRoutes::class,
    'logout'             => LogoutRoutes::class,
    'lock-screen'        => LockRoutes::class,
];
```

`RedotAuthManager::routes()` iterates this map in order. For each feature it:

1. Skips the feature when `$context->featureEnabled($feature)` is false (see [disabling features](#disabling-features)).
2. Picks an override from the `$registrars` argument if present, otherwise the default class.
3. Calls `app($class)->register($context)`.

```php
public function routes(
    string $guard,
    ?Closure $scope = null,
    array $views = [],
    array $disable = [],
    array $registrars = [],
    mixed $home = null,
): void
```

> Note: only the five features listed in `AuthContext::resolveDisabled()` (`register`, `magic-link`, `email-verification`, `logout`, `lock-screen`) can be turned off via `disable`. `login` and `password-reset` are always registered. `lock-screen` is additionally force-disabled whenever the guard is an API guard or no `unlock` view is configured.

### The `AuthContext` helpers registrars rely on

Registrars use a handful of context helpers when declaring routes:

- `$context->api` — `true` for `sanctum`, `passport`, `jwt`, or `api` guard drivers. API contexts skip view (`GET`) routes and skip the magic-link and lock registrars entirely.
- `$context->guest()` — `['guest:{guard}']` middleware for unauthenticated routes.
- `$context->auth()` — `['auth:{guard}']`, plus the `Locked` middleware when `lock-screen` is enabled.
- `$context->lockedMiddleware()` — the `Redot\Auth\Middleware\Locked:{guard},{unlock-route}` string.
- `$context->views[...]` — the view name map passed to `routes()`.
- `$context->featureEnabled('lock-screen')` — used by `LogoutRoutes` to strip the lock middleware off the logout route.

Route names are registered without a prefix; the prefix comes from the surrounding route group's `as`, which `RedotAuthManager::currentNamePrefix()` reads off the router's group stack.

## Routes by registrar

URIs and route names below are relative; in practice they inherit the prefix/`as`/middleware of the group that wraps the `RedotAuth::routes()` call.

### `LoginRoutes` (`login`)

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| GET | `login` | `login` | Web only (skipped when `api`) |
| POST | `login` | `login.store` | |

Both run under `guest:{guard}`. The `GET` renders `views['login']`; `POST` calls `Login::authenticate()`.

### `RegistrationRoutes` (`register`)

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| GET | `register` | `register` | Web only |
| POST | `register` | `register.store` | |

Guest middleware; `POST` calls `Registration::register()`.

### `PasswordResetRoutes` (`password-reset`)

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| GET | `forgot-password` | `password.request` | Web only |
| GET | `reset-password/{token}` | `password.reset` | Web only |
| POST | `forgot-password` | `password.email` | `PasswordReset::sendResetLink()` |
| POST | `reset-password` | `password.store` | `PasswordReset::reset()` |

All guest middleware. Cannot be disabled.

### `MagicLinkRoutes` (`magic-link`)

Skipped entirely for API contexts. All routes are guest-middleware web routes backed by `Redot\Auth\Actions\MagicLink`.

| Method | URI | Name |
| --- | --- | --- |
| GET | `magic-link` | `magic-link.create` |
| POST | `magic-link` | `magic-link.store` |
| GET | `magic-link/verify/{token}` | `magic-link-code.show` |
| GET | `magic-link/code` | `magic-link-code.create` |
| POST | `magic-link/code` | `magic-link-code.store` |

### `EmailVerificationRoutes` (`email-verification`)

Runs under `auth:{guard}`.

| Method | URI | Name | Middleware |
| --- | --- | --- | --- |
| GET | `verify-email` | `verification.notice` | Web only |
| GET | `verify-email/{id}/{hash}` | `verification.verify` | `signed`, `throttle:6,1` |
| POST | `email/verification-notification` | `verification.send` | `throttle:6,1` |

The verify route type-hints `Illuminate\Foundation\Auth\EmailVerificationRequest`.

### `LogoutRoutes` (`logout`)

Runs under `auth:{guard}`. The method depends on context: `DELETE logout` for API, `POST logout` for web. Both are named `logout` and call `Logout::logout()`. When `lock-screen` is enabled, the lock middleware is stripped from this route via `withoutMiddleware($context->lockedMiddleware())` so a locked user can still log out.

### `LockRoutes` (`lock-screen`)

Skipped entirely for API contexts. Runs under `auth:{guard}` and is backed by `Redot\Auth\Actions\Lock`.

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| POST | `lock` | `lock` | |
| GET | `unlock` | `unlock` | `withoutMiddleware(locked)` |
| POST | `unlock` | `unlock.store` | `withoutMiddleware(locked)` |

The unlock routes drop the `Locked` middleware so a locked-out user can reach the unlock screen. `lock-screen` is only active when an `unlock` view is supplied and the context is non-API.

## Usage

Register routes by calling the `RedotAuth` facade inside the route group whose prefix/`as`/middleware you want the auth routes to inherit. From the dashboard app:

```php
// routes/dashboard.php
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

Because an `unlock` view is provided and `register`/`email-verification` are disabled, this registers login, password reset, magic link, logout, and lock-screen routes for the `admins` guard.

### Disabling features

Pass feature keys to `disable`. The website guard keeps everything enabled:

```php
// routes/website.php
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

### API guards

For an API guard (driver `sanctum`/`passport`/`jwt`/`api`), `$context->api` is `true`, so view routes, magic-link, and lock-screen are skipped automatically. A bare call is enough:

```php
// routes/api/website.php
RedotAuth::routes(guard: 'users-api');

// routes/api/dashboard.php
RedotAuth::routes(
    guard: 'admins-api',
    scope: fn ($query) => $query->where('active', true),
    disable: [
        // ...
    ],
);
```

### Custom registrars

To override a feature's routes, pass your own class under the matching feature key. It must implement `Redot\Auth\Contracts\RouteRegistrar`:

```php
use Redot\Auth\Facades\RedotAuth;
use Redot\Auth\Contracts\RouteRegistrar;
use Redot\Auth\AuthContext;

class CustomLoginRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        // declare your own login routes using $context helpers
    }
}

RedotAuth::routes(
    guard: 'admins',
    registrars: ['login' => CustomLoginRoutes::class],
);
```

The override is only used for features that are enabled; the manager still skips disabled features regardless of any override supplied.

## Gotchas

- Route **names have no prefix of their own** — they pick up the `as` of the enclosing route group, read from the router's group stack at registration time. Register inside the group that owns your prefix.
- `login` and `password-reset` cannot be disabled.
- `lock-screen` silently disables itself for API guards or when no `unlock` view is set, even if you do not list it in `disable`.
- The logout route is `DELETE` for API guards and `POST` for web guards, but always named `logout`.
- Misconfigured guards throw `InvalidArgumentException` (unknown guard, missing provider, or invalid provider model) before any route is registered.
