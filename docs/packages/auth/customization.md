# Auth Customization

Redot Auth ships sensible defaults for every flow, but you can adjust the common bits — login identifiers, registration fields — without writing much, or replace an entire flow with your own. This page covers both. See [Register auth routes](/packages/auth/routes) for wiring up the routes themselves and [Auth actions](/packages/auth/actions) for what each flow does.

Start with the lightweight hooks below; reach for a full swap only when they aren't enough. Register all of this from a service provider's `boot()` method (except action swaps — see that section). The first argument to each hook is the **auth provider name** (the key under `config('auth.providers')`), not the guard.

## Change login identifiers and rules

By default users sign in with their `email`. Allow additional identifiers, or override the validation rules, per provider:

```php
use Redot\Auth\Actions\Login;

// Let "users" log in by email OR username.
Login::identifiers('users', ['email', 'username']);

// Override the rules for that provider.
Login::validationRules('users', fn ($context) => [
    $context->identifierInputName() => ['required'],
    'password' => ['required'],
]);
```

When a provider has a single identifier, the login field is named after that column (e.g. `email`); with more than one it becomes `identifier`. Build your form field with `$context->identifierInputName()` so it works either way.

## Customize registration

Capture extra fields by overriding the registration rules and the user-creation step per provider:

```php
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Actions\Registration;

Registration::validationRules('users', fn ($context) => [
    'first_name' => ['required', 'string', 'max:255'],
    'last_name'  => ['required', 'string', 'max:255'],
    'email'      => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
    'password'   => ['required', 'confirmed', Password::defaults()],
    ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
]);

Registration::createUserUsing('users', fn ($request, $context) => $context->model::create([
    'first_name' => $request->first_name,
    'last_name'  => $request->last_name,
    'email'      => $request->email,
    'password'   => $request->password,
]));
```

Without these hooks, registration validates `email` + `password` and creates the user with just those two fields.

## Customize magic links

Point the passwordless flow at your own login-token model or notification class:

```php
use Redot\Auth\Actions\MagicLink;

MagicLink::useLoginTokenModel(\App\Models\LoginToken::class);
MagicLink::useNotificationClass(\App\Notifications\MagicLink::class);
```

## Swap a whole flow

When the hooks aren't enough, replace an action entirely. Each flow is resolved from the service container, so bind your own implementation against the default action class in a service provider's `register()` method (not `boot()` — routes load before `boot()` runs):

```php
namespace App\Providers;

use App\Auth\CustomLogin;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Actions\Login;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Login::class, CustomLogin::class);
    }
}
```

Your class only has to produce the right response for each step of that flow. If you just want to tweak part of the behavior, extend the default action, override the one piece you care about, and bind your subclass. The same pattern applies to every flow: bind your replacement against `Registration`, `Logout`, `PasswordReset`, `MagicLink`, `EmailVerification`, or `Lock`.

Binding against a disabled feature has no effect — disabled features are never registered.

## Customize the lock screen

To change how unlocking works (for example a PIN instead of a password), swap the lock action using the binding approach above. The default unlock step re-checks the user's password.

If you protect routes outside the auth-generated group and want the same lock enforcement there, apply the lock middleware to that group:

```php
use Redot\Auth\Middleware\Locked;

Route::middleware([Locked::class . ':admins,dashboard.unlock'])->group(function () {
    // routes that also require the admin guard to be unlocked
});
```

The two arguments are the guard name and the route to redirect to for unlocking. For a normal web guard you don't do this yourself — registering an `unlock` view wires the middleware onto the auth routes automatically.

## Related

- [Register auth routes](/packages/auth/routes)
- [Auth actions](/packages/auth/actions)
- [Auth Overview](/packages/auth/overview)
