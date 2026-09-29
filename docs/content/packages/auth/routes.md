# Auth Panels & Routes

A panel is one authentication surface — for example "dashboard", "website", or "dashboard API". This page covers defining panels and registering their routes. See [Auth Overview](/packages/auth/overview) for the bigger picture.

## Usage

Define panels from a service provider's `boot()` method. This runs on every request, so callbacks such as `scope` are fine even when routes are cached:

```php
use Redot\Auth\Facades\RedotAuth;
use Redot\Auth\Features\EmailVerification;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Features\Login;
use Redot\Auth\Features\Logout;
use Redot\Auth\Features\MagicLink;
use Redot\Auth\Features\PasswordReset;

RedotAuth::panel('dashboard')
    ->guard('admins')
    ->scope(fn ($query) => $query->where('active', true))
    ->views('dashboard.auth')
    ->features(
        Login::make(),
        PasswordReset::make(),
        MagicLink::make(),
        EmailVerification::make(),
        Logout::make(),
        LockScreen::make()->protect('dashboard'),
    );
```

Register the routes in a route file, inside the group whose **name prefix and middleware** they should inherit:

```php
Route::name('dashboard.')->prefix('dashboard')->group(function () {
    RedotAuth::routes('dashboard');
});
```

Calling `RedotAuth::panel()` again with the same name returns the same panel, so you can add to it from another provider.

## Options

- **`guard`** — the guard to authenticate against (a key in `config/auth.php`). The user model and password broker are read from it.
- **`scope`** — a query callback that limits who can sign in, e.g. only active admins.
- **`views`** — a view prefix (`'dashboard.auth'` renders `dashboard.auth.login`, …), or an array that overrides single screens: `->views(['unlock' => 'shared.unlock'])`. The screens are `login`, `register`, `forgot-password`, `reset-password`, `magic-link`, `magic-link-code`, `verify-email`, and `unlock`. Each view receives the panel as `$panel`.
- **`home`** — where users go after signing in: a route name, or a callback that receives the panel and returns a URL. Defaults to the group's `index` route.
- **`identifiers`** — the columns users can sign in with. Defaults to `['email']`.
- **`api`** — makes the panel token-based: sign-in returns a bearer token, and screen routes are skipped. Required for token guards such as Sanctum; registering the routes throws if a token guard's panel isn't marked `api()`.
- **`features`** — the features to enable. Nothing is enabled unless you list it. See [Features reference](/packages/auth/actions).
- **`using`** — replaces a piece of behavior for this panel only. See [Customize auth](/packages/auth/customization).

## Examples

### An API panel

API panels only get the JSON endpoints. Magic links and the lock screen rely on the session, so listing them on an API panel throws an error.

```php
RedotAuth::panel('dashboard-api')
    ->guard('admins-api')
    ->api()
    ->features(
        Login::make(),
        PasswordReset::make(),
        Logout::make(),
    );

// routes/api/dashboard.php
Route::prefix('auth')->group(function () {
    RedotAuth::routes('dashboard-api');
});
```

### Signing in by email or username

With more than one identifier, the login field is named `identifier` instead of the column name. Use `$panel->identifierInputName()` in your view so the form works either way.

```php
RedotAuth::panel('website')
    ->guard('users')
    ->identifiers(['email', 'username']);
```

### Protecting other routes with the lock screen

With `LockScreen::make()` enabled, the panel's authenticated routes redirect to the unlock screen while the session is locked. To lock the rest of your pages too, name the middleware groups they use:

```php
LockScreen::make()->protect('dashboard')
```

Every route in the `dashboard` middleware group now sends a locked user to the panel's unlock screen, including routes defined outside the panel. It keeps working with cached routes. The group must exist once your service providers have booted. A lock is dropped automatically if the user is no longer signed in.

## Related

- [Features reference](/packages/auth/actions)
- [Customize auth](/packages/auth/customization)
