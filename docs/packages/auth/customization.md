# Auth Customization

The auth package ships a default implementation for every authentication flow (login, registration, logout, password reset, magic link, email verification and the lock screen), but every flow is driven by an interface and resolved out of the service container. That means you can tweak the small customizable hooks, or swap an entire flow for your own implementation, without touching the package.

This page covers the action contracts, how route registrars resolve the action classes, how to bind your own implementation, and the `Locked` middleware. See [Auth Overview](/packages/auth/overview) for the route registration story and [AuthContext](/packages/auth/overview) for the context object that every action receives.

## How actions are resolved

`RedotAuth::routes()` walks a fixed list of route registrars (one per feature). Each registrar resolves its action out of the container by its **concrete class**, not by its contract, and binds it to the route closures:

```php
// Redot\Auth\Routes\LoginRoutes
$action = app(Login::class);

Route::post('login', fn (Request $request) => $action->authenticate($request, $context))
    ->name('login.store');
```

The concrete defaults are:

| Feature             | Contract                                       | Default action                       |
| ------------------- | ---------------------------------------------- | ------------------------------------ |
| `login`             | `Redot\Auth\Contracts\LoginAction`             | `Redot\Auth\Actions\Login`           |
| `register`          | `Redot\Auth\Contracts\RegistrationAction`      | `Redot\Auth\Actions\Registration`    |
| `logout`            | `Redot\Auth\Contracts\LogoutAction`            | `Redot\Auth\Actions\Logout`          |
| `password-reset`    | `Redot\Auth\Contracts\PasswordResetAction`     | `Redot\Auth\Actions\PasswordReset`   |
| `magic-link`        | `Redot\Auth\Contracts\MagicLinkAction`         | `Redot\Auth\Actions\MagicLink`       |
| `email-verification`| `Redot\Auth\Contracts\EmailVerificationAction` | `Redot\Auth\Actions\EmailVerification` |
| `lock-screen`       | `Redot\Auth\Contracts\LockAction`              | `Redot\Auth\Actions\Lock`            |

Because the registrars call `app(Login::class)` (and so on for each concrete class), the contract is the design surface, but the **concrete class name is the container key you bind against**.

## The action contracts

Every action method receives the incoming `Illuminate\Http\Request` and the resolved `Redot\Auth\AuthContext` for the current guard. The context exposes the guard name, provider, model, route-name prefix, the `views` map, the `api` flag, and helpers such as `routeName()`, `homeUrl()` and `identifierInputName()`.

```php
namespace Redot\Auth\Contracts;

interface LoginAction
{
    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}

interface LogoutAction
{
    public function logout(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}

interface RegistrationAction
{
    public function register(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}

interface PasswordResetAction
{
    public function sendResetLink(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
    public function reset(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}

interface MagicLinkAction
{
    public function send(Request $request, AuthContext $context): RedirectResponse;
    public function verifyToken(string $token, AuthContext $context): RedirectResponse;
    public function view(Request $request, AuthContext $context): View|RedirectResponse;
    public function verifyCode(Request $request, AuthContext $context): RedirectResponse;
}

interface EmailVerificationAction
{
    public function prompt(Request $request, AuthContext $context): RedirectResponse|View;
    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
    public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}

interface LockAction
{
    public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
    public function view(Request $request, AuthContext $context): View|RedirectResponse;
    public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
}
```

## Lightweight customization first

Before replacing a whole action, check whether the small static hooks on the default actions already cover your need. They are the lightest way to change behavior and are what the Redot Dashboard uses.

The default `Login` action lets you register per-provider identifiers and validation rules:

```php
// Redot\Auth\Actions\Login
Login::identifiers('users', ['email', 'username']);
Login::validationRules('users', fn (AuthContext $context) => [...]);
```

When a provider has a single identifier its input field is named after that identifier (e.g. `email`); with multiple identifiers the input is named `identifier` (see `AuthContext::identifierInputName()`).

The default `Registration` action exposes per-provider validation rules and a user-creation callback. This is the real configuration the dashboard uses in `App\Providers\AppServiceProvider::configureAuth()`:

```php
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Actions\Registration;
use Redot\Auth\AuthContext;

Registration::validationRules('users', fn (AuthContext $context) => [
    'first_name' => ['required', 'string', 'max:255'],
    'last_name'  => ['required', 'string', 'max:255'],
    'email'      => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
    'password'   => ['required', 'confirmed', Password::defaults()],
    ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
]);

Registration::createUserUsing('users', fn (Request $request, AuthContext $context) => $context->model::create([
    'first_name' => $request->first_name,
    'last_name'  => $request->last_name,
    'email'      => $request->email,
    'password'   => $request->password,
]));
```

The first argument to these helpers is the **auth provider name** (the key under `config('auth.providers')`), not the guard. If no rules/callback are registered for a provider, the default action falls back to validating `email` + `password` and creating the user with `$request->only('email', 'password')`.

## Swapping an action implementation

When the static hooks are not enough, replace the action entirely. Implement the relevant contract (or extend the default action) and bind it to the **concrete default class** in a service provider's `register()` method:

```php
namespace App\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\LoginAction;

class CustomLogin implements LoginAction
{
    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        // your own validation, throttling, credential check, token issuing...

        return redirect()->intended($context->homeUrl());
    }
}
```

```php
namespace App\Providers;

use App\Auth\CustomLogin;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Actions\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrars call app(Login::class), so bind against the concrete class.
        $this->app->bind(Login::class, CustomLogin::class);
    }
}
```

Because the registrar resolves `app(Login::class)`, binding the contract `LoginAction` alone will **not** take effect — bind the concrete `Redot\Auth\Actions\Login` key. The same applies to every other feature: bind `Redot\Auth\Actions\Registration`, `...\Logout`, `...\PasswordReset`, `...\MagicLink`, `...\EmailVerification` or `...\Lock` to swap the corresponding flow.

If you only need to adjust part of the behavior, extend the default action and override the method you care about, then bind the subclass:

```php
use Redot\Auth\Actions\Registration as BaseRegistration;

class Registration extends BaseRegistration
{
    // override protected createUser()/rules() or the public register()
}

$this->app->bind(BaseRegistration::class, Registration::class);
```

> Bind in `register()`, not `boot()` — route registration happens while route files load, which can run before `boot()` on other providers. Binding during `register()` guarantees the override is in place when the registrar resolves the action.

## The Locked middleware

`Redot\Auth\Middleware\Locked` enforces the lock screen. It is applied automatically by `AuthContext::auth()` whenever the `lock-screen` feature is enabled, via `AuthContext::lockedMiddleware()`:

```php
// Redot\Auth\AuthContext::lockedMiddleware()
Locked::class . ':' . $this->guard . ',' . $this->routeName('unlock');
```

The middleware signature is:

```php
public function handle(
    Request $request,
    Closure $next,
    string $guard = 'web',
    string $unlockRoute = 'unlock'
): Response;
```

Behavior:

- It checks the session key produced by `Redot\Auth\Actions\Lock::sessionKey($guard)`, which is `"auth.{$guard}.locked"`.
- If that key is set and the current route is **not** the `$unlockRoute`, it stores `url()->previous()` in `url.intended` and redirects to the named unlock route.
- Otherwise it passes the request through.

The lock screen is automatically disabled for API guards and for guards that do not define an `unlock` view (see `AuthContext::resolveDisabled()`), so for a typical web guard you do not register this middleware yourself — `RedotAuth::routes()` wires it on the authenticated route group. You would only reference it directly if you protect additional routes outside the auth-generated group and want the same lock enforcement:

```php
use Redot\Auth\Middleware\Locked;

Route::middleware([Locked::class . ':admins,dashboard.unlock'])->group(function () {
    // routes that also require the admin guard to be unlocked
});
```

To customize lock/unlock behavior itself (for example a PIN instead of a password), bind your own implementation of `Redot\Auth\Contracts\LockAction` to `Redot\Auth\Actions\Lock` as shown above. The default `Lock` action verifies the unlock attempt with `Hash::check()` against the authenticated user's `password` and clears the session key on success.

## Gotchas

- **Bind the concrete class, not the contract.** Registrars resolve `app(ConcreteAction::class)`; an interface-only binding is ignored.
- **Provider, not guard.** `Login::identifiers()`, `Login::validationRules()`, `Registration::validationRules()` and `Registration::createUserUsing()` are keyed by the auth provider name.
- **API guards skip the lock screen.** `Lock::lock()`/`unlock()` return a 400 failure for API contexts, and the feature is force-disabled for API guards or guards without an `unlock` view.
- **Disabled features are never registered.** If you disable a feature via the `disable:` array on `RedotAuth::routes()`, its registrar (and therefore its action) is skipped entirely, so binding an override for a disabled feature has no effect.
