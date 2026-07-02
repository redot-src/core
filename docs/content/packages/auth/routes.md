# Auth Routes

This page covers how to register a guard's authentication routes — see [Auth Overview](/packages/auth/overview) for the bigger picture. One `RedotAuth::routes()` call registers a full auth surface (login, two-factor authentication, logout, registration, password reset, magic links, email verification, lock screen) for the guard you name.

## Usage

Call `RedotAuth::routes()` inside the route group whose **name prefix and middleware** you want the generated routes to inherit. The prefix is read from the surrounding group at call time, so `login`, `logout`, `password.request`, and friends pick it up automatically.

```php
use Redot\Auth\Facades\RedotAuth;

Route::name('admin.')->group(function () {
    RedotAuth::routes(
        guard: 'admin',
        scope: fn ($query) => $query->where('active', true),
        views: [
            'login' => 'admin.auth.login',
            'forgot-password' => 'admin.auth.forgot-password',
            'reset-password' => 'admin.auth.reset-password',
            'magic-link' => 'admin.auth.magic-link',
            'magic-link-code' => 'admin.auth.magic-link-code',
            'unlock' => 'admin.auth.unlock',
        ],
        disable: ['register', 'email-verification'],
    );
});
```

## Options

- **`guard`** — the guard to authenticate against (a key under `config/auth.php`). The package reads its provider and model for you.
- **`scope`** — an optional query callback that constrains which users can authenticate, e.g. only active admins.
- **`views`** — maps each screen (`login`, `register`, `forgot-password`, `reset-password`, `magic-link`, `magic-link-code`, `two-factor-challenge`, `two-factor`, `verify-email`, `unlock`) to a Blade view. Each view receives the resolved auth context as `$context`. Omit a screen to skip rendering it.
- **`disable`** — turn off optional features by name: `register`, `magic-link`, `email-verification`, `logout`, `lock-screen`, `two-factor`. Login and password reset are always registered.
- **`registrars`** — supply your own route definitions for a feature, keyed by feature name (see below).
- **`home`** — where to send the user after login: a route name, a callback returning a URL, or omit it to use the section's `index` route.

## Examples

### A web guard with everything enabled

The public-facing `web` guard keeps registration, magic links, and email verification on — just map every screen and pass no `disable`:

```php
RedotAuth::routes(
    guard: 'web',
    views: [
        'login' => 'auth.login',
        'register' => 'auth.register',
        'forgot-password' => 'auth.forgot-password',
        'reset-password' => 'auth.reset-password',
        'magic-link' => 'auth.magic-link',
        'magic-link-code' => 'auth.magic-link-code',
        'verify-email' => 'auth.verify-email',
    ],
);
```

### An API guard

For a token-based guard (Sanctum, Passport, JWT) no views are needed — only the JSON endpoints are registered, and the login flow returns a bearer token instead of starting a session. The magic-link and lock-screen features are skipped automatically.

```php
// routes/api/web.php
RedotAuth::routes(guard: 'users-api');

// routes/api/admin.php
RedotAuth::routes(
    guard: 'admins-api',
    scope: fn ($query) => $query->where('active', true),
    disable: ['register', 'email-verification'],
);
```

### Enabling the lock screen

The lock screen turns on as soon as you provide an `unlock` view (and the guard is web-based). With it enabled, authenticated routes in the group require the user to re-enter their password after locking. To extend the same protection to routes outside the auth group, or to swap how unlocking works, see [Customize auth](/packages/auth/customization).

### Enabling two-factor authentication

On web guards, two-factor authentication turns on as soon as you provide a `two-factor-challenge` view; map a `two-factor` view as well to get a settings screen at `GET two-factor`. On API guards it is always on and needs no views — login answers with a challenge token and every endpoint speaks JSON. The guard's model must use the `Redot\Auth\Concerns\TwoFactorAuthenticatable` trait and have the `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, and `two_factor_email_confirmed_at` columns.

With it enabled, users who turned on a two-factor method must pass the challenge after entering valid credentials, and per-method management endpoints (`two-factor/{method}` enable/confirm/delete, recovery-code regeneration) are registered behind the auth middleware. See [Auth actions](/packages/auth/actions#two-factor-authentication) for the full flow and [Customize auth](/packages/auth/customization#add-a-two-factor-method) for adding your own methods.

### Replacing a feature's routes

To define your own routes for one feature while keeping the rest, pass a replacement under `registrars`, keyed by the feature name:

```php
RedotAuth::routes(
    guard: 'admins',
    registrars: ['login' => CustomLoginRoutes::class],
);
```

The replacement is used only for features that are enabled; disabled features stay skipped.

## Related

- [Customize auth](/packages/auth/customization) — swap actions and change validation.
- [Auth actions reference](/packages/auth/actions) — what each registered route does.
