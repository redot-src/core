# Auth Features

Each feature adds one authentication flow to a [panel](/packages/auth/routes). Enable features with `->features(...)`. Every flow adapts to the panel: web panels start a session and redirect, API panels return JSON and issue bearer tokens. Routes that only show a screen (like `login` or `password.request`) are registered on web panels only.

## Login

```php
Login::make()
```

Validates the credentials, throttles repeated failures by identifier and IP, and checks the password. On success, web panels log the user in (honoring a `remember` checkbox) and API panels return a token. Adds `login` and `login.store`.

- **`rules`** — replace the validation rules with an array, or adjust them with a callback that receives the default rules and the panel: `fn (array $rules, Panel $panel) => [...$rules, 'captcha' => ['required', 'captcha']]`.
- **`throttle`** — failed attempts allowed, and for how many seconds each one counts. Defaults to 5 per 60 seconds.
- **`steps`** — change how the credentials are checked, with an array or a callback that receives the default steps and the panel. Steps that run after the user is verified, such as a two-factor challenge, come from features instead. See [Customize auth](/packages/auth/customization#add-a-login-step).

## Registration

```php
Registration::make()
```

Validates the request, creates the user, and fires Laravel's `Registered` event, which sends the verification email when your model requires it. Web panels are then logged in; API panels get a token and a `201`. Adds `register` and `register.store`. Out of the box it takes a unique `email` and a confirmed `password`. To collect more fields, see [Customize auth](/packages/auth/customization#customize-registration).

## Password reset

```php
PasswordReset::make()
```

Wraps the guard's password broker. The forgot-password screen emails a reset link, and the reset screen sets a new confirmed password and fires the `PasswordReset` event. Adds `password.request`, `password.email`, `password.reset`, and `password.store`.

## Magic link

```php
MagicLink::make()->expiresIn(15)->throttle(5, 3600)
```

Passwordless sign-in: the user enters their identifier and receives an email with both a link and a 6-character code. Requests are throttled the same way whether or not the identifier exists. The email is sent after the response, so response times don't reveal which accounts exist; if sending fails, the error is logged rather than shown to the user. Password reset emails are sent the same way. Web panels only. Adds `magic-link.create`, `magic-link.store`, `magic-link.verify`, `magic-link-code.create`, and `magic-link-code.store`.

- **`expiresIn`** — minutes a link stays valid. Defaults to `auth.magic_link.expire`, or 15.
- **`throttle`** — link requests allowed, and for how many seconds each one counts. Defaults to `auth.magic_link.throttle.max_attempts` and `decay_minutes`, or 5 per hour.

Set these options when one panel needs different values. Otherwise the app-wide `auth.magic_link` config applies:

```php
// config/auth.php
'magic_link' => [
    'expire' => env('AUTH_MAGIC_LINK_EXPIRE', 15), // minutes
    'throttle' => [
        'max_attempts' => env('AUTH_MAGIC_LINK_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('AUTH_MAGIC_LINK_DECAY_MINUTES', 60),
    ],
],
```
- **`notification`** — send a different email. See [Notifications](/foundation/notifications).
- **`tokenModel`** — store tokens with your own model.

## Email verification

```php
EmailVerification::make()
```

Shows the "verify your email" prompt, handles the signed verification link, and resends the email on request (rate-limited). Users who are already verified are sent home. Adds `verification.notice` (web only), `verification.verify`, and `verification.send`.

## Logout

```php
Logout::make()
```

Ends the session on web panels, or revokes the current token on API panels. It stays reachable while the session is locked. Adds `logout`.

## Lock screen

```php
LockScreen::make()->protect('dashboard')
```

Lets a signed-in user lock their session and unlock it later with their password. Unlock attempts are rate-limited. While locked, the panel's authenticated routes redirect to the unlock screen. Web panels only. Adds `lock`, `unlock`, and `unlock.store`.

- **`protect`** — middleware groups whose routes also redirect to the unlock screen while locked. See [Protecting other routes](/packages/auth/routes#protecting-other-routes-with-the-lock-screen).

## Related

- [Define panels and register routes](/packages/auth/routes)
- [Customize auth](/packages/auth/customization)
