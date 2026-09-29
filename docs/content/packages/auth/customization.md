# Auth Customization

Most changes are a panel or feature option. When you need different behavior, swap the piece that does it. This page covers where each setting lives, then the swaps you'll use most.

## Where settings live

- **Panel options** apply to every feature on the panel: `guard`, `scope`, `views`, `home`, `identifiers`, `api`. See [Panels & routes](/packages/auth/routes).
- **Feature options** tune one flow: login `rules`, `throttle`, and `steps`; magic link `expiresIn`, `throttle`, `notification`, and `tokenModel`; lock screen `protect`. See [Features reference](/packages/auth/actions).
- **`using()`** swaps a class for one panel: the user creator (`CreatesUsers`), any single response (such as `LoginResponse`), or the `Authenticator` that signs users in. It accepts a class name, or a callback that receives the panel and any constructor data (such as the user for `LoginResponse`) and returns the instance.
- **A container binding** (`$this->app->bind(CreatesUsers::class, ...)`) sets the default for every panel that doesn't call `using()`.
- **Config** provides app-wide defaults where a setting is usually tuned per environment: the magic-link lifetime and throttle (`auth.magic_link`). A feature option set on a panel takes precedence over it.

Define panels and their features in a service provider's `boot()`, and don't change them afterwards: the same objects serve every request. To share setup between panels, build the features in a small method that returns a fresh object each time, rather than passing one feature object to two panels.

## Customize registration

Registration asks a user creator for the validation rules, then passes it the validated input. Write your own to collect more fields:

```php
namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Panel;

class CreateUser implements CreatesUsers
{
    public function rules(Panel $panel): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:' . $panel->model()],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function create(array $input, Panel $panel): User
    {
        return User::create($input);
    }
}
```

Then use it on the panel:

```php
RedotAuth::panel('website')->using(CreatesUsers::class, CreateUser::class);
```

## Add a login step

Signing in happens in two phases. First the credentials are checked (the login feature's `steps`), then the user is signed in. Features can add steps between the two by overriding `verifiedSteps`. These run for every sign-in on the panel, whether by password or magic link, so a two-factor challenge can't be skipped:

```php
use Illuminate\Support\Facades\Route;
use Redot\Auth\Features\Feature;
use Redot\Auth\Panel;

class TwoFactor extends Feature
{
    public function verifiedSteps(array $steps): array
    {
        return [RedirectToTwoFactorChallenge::class, ...$steps];
    }

    public function supportsApi(): bool
    {
        return false;
    }

    public function routes(Panel $panel): void
    {
        Route::middleware($panel->guestMiddleware())->group(function () {
            Route::get('two-factor', [TwoFactorController::class, 'create'])->name('two-factor.challenge');
            Route::post('two-factor', [TwoFactorController::class, 'store'])->middleware('throttle:6,1')->name('two-factor.store');
        });
    }
}
```

A step receives the login attempt (`request`, `panel`, `identifier`, `remember`, and the verified `user`) and either passes it on or returns its own response. This one remembers who is signing in and shows the challenge instead:

```php
use Closure;
use Redot\Auth\Pipeline\LoginAttempt;

class RedirectToTwoFactorChallenge
{
    public function handle(LoginAttempt $attempt, Closure $next): mixed
    {
        if (! $attempt->user->two_factor_enabled) {
            return $next($attempt);
        }

        $attempt->request->session()->put('login', [
            'id' => $attempt->user->getKey(),
            'identifier' => $attempt->identifier,
            'remember' => $attempt->remember,
        ]);

        return redirect()->route($attempt->panel->routeName('two-factor.challenge'));
    }
}
```

Once the code checks out, the challenge controller finishes signing the user in. Controllers on a panel's routes can type-hint `Panel $panel` to receive the current panel:

```php
use Redot\Auth\Authenticator;

$login = $request->session()->pull('login');

$attempt = new LoginAttempt(
    request: $request,
    panel: $panel,
    identifier: $login['identifier'],
    remember: $login['remember'],
    user: $panel->query()->findOrFail($login['id']),
);

return $panel->make(Authenticator::class)->complete($attempt);
```

Enable it next to the other features: `->features(Login::make(), TwoFactor::make())`. Registration signs new users in directly, without these steps.

To change how credentials are checked instead, use the login feature's `steps` option. To add a captcha or other fields to the login form, adjust its `rules`.

## Change the responses

What users see after each flow comes from one small response class per flow. Each class has a `web()` method for browser panels and an `api()` method for API panels, and the right one runs automatically. To change one, extend the class and override only the side you care about:

```php
use Redot\Auth\Panel;
use Redot\Auth\Responses\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

class WelcomeLoginResponse extends LoginResponse
{
    protected function web(Panel $panel): Response
    {
        return redirect()->route('dashboard.welcome');
    }
}
```

```php
RedotAuth::panel('dashboard')->using(LoginResponse::class, WelcomeLoginResponse::class);
```

API panels keep the default JSON, because only `web()` changed. Each response receives its data in the constructor, so it's available as a property, such as `$this->user` and `$this->token` on `LoginResponse`, or `$this->status` on the password responses.

The responses are:

- **`LoginResponse`** — after signing in (`$this->user`, `$this->token`).
- **`RegisterResponse`** — after signing up (`$this->user`, `$this->token`).
- **`LogoutResponse`** — after signing out.
- **`ResetLinkSentResponse`** — after asking for a password reset link (`$this->status`).
- **`PasswordResetResponse`** — after setting a new password (`$this->status`).
- **`EmailVerifiedResponse`** — after following an email verification link (`$this->alreadyVerified`).
- **`VerificationLinkSentResponse`** — after asking for a new verification link (`$this->alreadyVerified`).
- **`MagicLinkSentResponse`** — after asking for a magic link (`$this->identifier`, web only).
- **`UnlockedResponse`** — after unlocking the lock screen (web only).

Several responses send users to the panel's `home` by default, so overriding one of them replaces `home` for that flow only. A response without an `api()` or `web()` method throws a clear error if it's ever used on that kind of panel. To change a response for every panel, bind it in the container instead of calling `using()`.

## Related

- [Define panels and register routes](/packages/auth/routes)
- [Features reference](/packages/auth/actions)
