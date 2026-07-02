# Auth Overview

Redot Auth registers a complete authentication stack — login, two-factor authentication, logout, registration, password reset, magic links, email verification, and a lock screen — for any guard with a single call. You describe a guard and which screens it uses, and the package wires up the routes, validation, throttling, and redirects for you. The same setup works for both web (session) and API (token) guards.

## Quick start

Inside the route group whose name prefix and middleware you want the auth routes to inherit, point `RedotAuth::routes()` at a guard from your `config/auth.php` and map each screen to a Blade view:

```php
use Redot\Auth\Facades\RedotAuth;

RedotAuth::routes(
    guard: 'admin',
    views: [
        'login' => 'admin.auth.login',
        'forgot-password' => 'admin.auth.forgot-password',
        'reset-password' => 'admin.auth.reset-password',
    ],
    disable: ['register', 'email-verification'],
);
```

That registers login, logout, and password-reset routes for the `admin` guard and renders your views for the matching screens. An API guard needs no views at all — a bare `RedotAuth::routes(guard: 'api')` registers JSON endpoints that issue tokens.

## Common tasks

- [Register auth routes](/packages/auth/routes) — choose a guard, map screens to views, enable/disable features, and set the post-login redirect.
- [Customize auth actions](/packages/auth/customization) — change login identifiers and registration rules, or swap a whole flow for your own.
- [Auth actions reference](/packages/auth/actions) — what each flow (login, magic link, lock screen, …) does and how to tune it.

## Related

- [Components overview](/components/overview) — the form fields you build login/register screens with.
- [Input](/components/input) — the standard text field used in auth forms.
