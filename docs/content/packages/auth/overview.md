# Auth Overview

Redot Auth gives any guard a complete authentication stack — login, logout, registration, password reset, magic links, email verification, and a lock screen. You describe each **panel** once in a service provider (its guard, views, and features), then register its routes with one line. The same setup works for web (session) and API (token) guards, and it's fully compatible with `route:cache` and `config:cache`.

## Quick start

Define the panel in a service provider's `boot()` method:

```php
use Redot\Auth\Facades\RedotAuth;
use Redot\Auth\Features\Login;
use Redot\Auth\Features\Logout;
use Redot\Auth\Features\PasswordReset;

RedotAuth::panel('admin')
    ->guard('admins')
    ->views('admin.auth')
    ->features(
        Login::make(),
        PasswordReset::make(),
        Logout::make(),
    );
```

Then register its routes inside the route group whose name prefix and middleware they should inherit:

```php
Route::name('admin.')->prefix('admin')->group(function () {
    RedotAuth::routes('admin');
});
```

That registers `admin.login`, `admin.password.request`, `admin.logout`, and friends, rendering `admin.auth.login`, `admin.auth.forgot-password`, and so on.

## Common tasks

- [Define panels and register routes](/packages/auth/routes) — pick a guard, views, and features; set up API panels and the lock screen.
- [Features reference](/packages/auth/actions) — what each feature does, the routes it adds, and its options.
- [Customize auth](/packages/auth/customization) — custom registration, extra login steps (2FA, captcha), and your own responses.

## Related

- [Components overview](/components/overview) — the form fields you build login/register screens with.
- [Input](/components/input) — the standard text field used in auth forms.
