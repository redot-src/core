# Auth Actions

Auth actions are the swappable, single-responsibility classes that contain all of the actual behavior behind Redot Auth's routes — logging in, registering, sending magic links, resetting passwords, verifying email, and the lock screen. Each action implements a contract interface (in `Redot\Auth\Contracts`) and receives the request plus an [`AuthContext`](/packages/auth/overview), so the same class works across multiple guards (web, API, website, dashboard) without branching at the route level.

## How actions fit together

Every action method has the same shape: it takes the `Illuminate\Http\Request` and a `Redot\Auth\AuthContext`, then returns a `RedirectResponse`, a `JsonResponse`, or a `View` depending on the operation and whether `$context->api` is `true`.

The `AuthContext` carries everything an action needs to stay guard-agnostic:

- `$context->guard`, `$context->provider`, `$context->broker`, `$context->model` — the auth wiring for this guard.
- `$context->api` — when `true`, actions return JSON (via the `RespondAsApi` trait) and issue Sanctum tokens instead of starting a session.
- `$context->identifiers` / `$context->identifierInputName()` — the columns a user may log in with (defaults to `['email']`). With a single identifier the input field name is that column; with more than one it becomes `identifier`.
- `$context->routeName('login')`, `$context->homeUrl()`, `$context->views[...]` — name-prefixed route names, the post-auth redirect target, and the view map.

Because each action implements an interface, you can replace any one of them with your own implementation. See [Customizing Auth](/packages/auth/customization) for binding overrides. The shared helpers `QueriesUsers` (user lookup by identifier, `last_login_at` touch) and `RateLimitsRequests` (throttle keys, lockout) are mixed into the actions that need them.

JSON responses use the `Redot\Traits\RespondAsApi` trait, which returns `{ code, success, message, payload }` from `respond()` and throws an `HttpResponseException` with the same shape from `fail()`.

## Login

`Redot\Auth\Actions\Login` implements `Redot\Auth\Contracts\LoginAction`.

```php
public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

`authenticate()` validates the request, enforces rate limiting, finds the user by the configured identifier(s), and checks the password with `Hash::check`. On failure it hits the rate limiter and throws a validation error keyed to the identifier input with the `auth.failed` message. On success it touches `last_login_at`, clears the throttle, and either:

- returns a Sanctum `auth_token` (`{ token, token_type: "Bearer" }`) when `$context->api` is `true`, or
- logs the user into `$context->guard` (honoring the `remember` checkbox), regenerates the session, and redirects to `redirect()->intended($context->homeUrl())`.

Static configuration (call from a service provider's `boot()`):

```php
// Allow logging in by email OR username for the "users" provider.
Login::identifiers('users', ['email', 'username']);

// Override the validation rules for a provider (array or Closure(AuthContext)).
Login::validationRules('users', fn (AuthContext $context) => [
    $context->identifierInputName() => ['required'],
    'password' => ['required'],
]);
```

`Login::getIdentifiers($provider)` returns the registered identifiers (defaulting to `['email']`). When no custom rules are set, the default rules require the identifier input and `password`.

## Logout

`Redot\Auth\Actions\Logout` implements `Redot\Auth\Contracts\LogoutAction`.

```php
public function logout(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

For API contexts it deletes the current Sanctum access token and returns a success JSON message. For session contexts it logs out of `$context->guard`, invalidates the session, regenerates the CSRF token, and redirects to `$context->homeUrl()`.

## Registration

`Redot\Auth\Actions\Registration` implements `Redot\Auth\Contracts\RegistrationAction`.

```php
public function register(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

`register()` validates the request, creates the user, fires `Illuminate\Auth\Events\Registered` (which triggers email verification when the model implements `MustVerifyEmail`), and then returns a `201` JSON token response for API contexts or logs the user in and redirects for session contexts.

The default rules require a unique `email` and a confirmed `password` (using `Rules\Password::defaults()`). The default user creation persists only `email` and `password`. Both are overridable per provider:

```php
public static function validationRules(string $provider, array|Closure $rules): void;
public static function createUserUsing(string $provider, Closure $callback): void;
```

The Redot Dashboard app overrides both in `App\Providers\AppServiceProvider::boot()` to capture extra fields and an optional Turnstile captcha:

```php
Registration::validationRules('users', fn (AuthContext $context) => [
    'first_name' => ['required', 'string', 'max:255'],
    'last_name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
    'password' => ['required', 'confirmed', Password::defaults()],
    ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
]);

Registration::createUserUsing('users', fn (Request $request, AuthContext $context) => $context->model::create([
    'first_name' => $request->first_name,
    'last_name'  => $request->last_name,
    'email'      => $request->email,
    'password'   => $request->password,
]));
```

## MagicLink

`Redot\Auth\Actions\MagicLink` implements `Redot\Auth\Contracts\MagicLinkAction`. This is the passwordless flow: a user requests a link, receives an email containing both a clickable link and a 6-digit code, and authenticates with either.

```php
public function send(Request $request, AuthContext $context): RedirectResponse;
public function verifyToken(string $token, AuthContext $context): RedirectResponse;
public function view(Request $request, AuthContext $context): View|RedirectResponse;
public function verifyCode(Request $request, AuthContext $context): RedirectResponse;
```

- `send()` validates the identifier, rate-limits requests (using the `magic-link` prefix and the `auth.magic_link.throttle.max_attempts` / `decay_minutes` config), finds the user, generates a `Redot\Models\LoginToken` via `LoginToken::generate($user->email, $context->guard)`, and notifies the user. It then redirects to the code-entry screen with the email base64-encoded in the URL. If no user matches, it hits the limiter and throws an `auth.failed` validation error.
- `verifyToken()` is the link path: it looks up the token with `findByToken($token, $context->guard)` and authenticates, or redirects back with an error if the token is missing/expired.
- `view()` renders the `magic-link-code` view, but only if a valid token exists for the decoded email; otherwise it redirects to the request screen.
- `verifyCode()` validates `email` and a 6-character `code`, resolves the token via `findByCode(...)`, and authenticates (throwing a validation error on a bad/expired code).

The shared `authenticate()` step deletes the consumed token, logs the user in, touches `last_login_at`, regenerates the session, and redirects to the intended URL.

Pluggable models/notification (call from a service provider):

```php
MagicLink::useLoginTokenModel(\App\Models\LoginToken::class);     // default: Redot\Models\LoginToken
MagicLink::useNotificationClass(\App\Notifications\MagicLink::class); // default: Redot\Notifications\MagicLinkNotification
```

The default `Redot\Notifications\MagicLinkNotification` is a mail notification built with `loginToken` and a `verifyRoute` name. Its mail body includes the code, an action button to `route($verifyRoute, ['token' => $loginToken->token])`, and an expiry line driven by `config('auth.magic_link.expire', 15)` minutes.

## PasswordReset

`Redot\Auth\Actions\PasswordReset` implements `Redot\Auth\Contracts\PasswordResetAction`.

```php
public function sendResetLink(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
public function reset(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

These wrap Laravel's `Password` broker, selected per guard via `Password::broker($context->broker)`:

- `sendResetLink()` validates `email`, sends the reset link, and returns the `Password::RESET_LINK_SENT` status as JSON (API) or a `success` flash redirect back (session).
- `reset()` validates `token`, `email`, and a confirmed `password` (`min:8` plus `Rules\Password::defaults()`), resets the password, sets a new `remember_token`, and fires `Illuminate\Auth\Events\PasswordReset`. On API contexts a non-success status throws a validation error; on session contexts a successful reset redirects to the `login` route with a `success` flash, otherwise it redirects back with the status error.

## EmailVerification

`Redot\Auth\Actions\EmailVerification` implements `Redot\Auth\Contracts\EmailVerificationAction`.

```php
public function prompt(Request $request, AuthContext $context): RedirectResponse|View;
public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
public function send(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

- `prompt()` redirects already-verified users home, otherwise renders the `verify-email` view.
- `verify()` marks the email verified (firing `Illuminate\Auth\Events\Verified`). Already-verified users get a `403` JSON failure (`already_verified: true`) on API, or a redirect to `homeUrl() . '?verified=1'` on session. Successful verification returns a success JSON (`already_verified: false`) or the same `?verified=1` redirect.
- `send()` re-sends the verification notification via `sendEmailVerificationNotification()`, returning a JSON message (API) or a `success` flash back (session). Already-verified users get a `400` failure / redirect home.

This action reads the current user through `$request->user()`, so the verification routes run behind the auth middleware.

## Lock

`Redot\Auth\Actions\Lock` implements `Redot\Auth\Contracts\LockAction`. This powers the session lock screen and is session-only — every method returns a `400` failure for API guards. The lock screen is also automatically disabled by `AuthContext` for API guards or when no `unlock` view is configured.

```php
public static function sessionKey(string $guard): string; // "auth.{guard}.locked"
public function lock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
public function view(Request $request, AuthContext $context): View|RedirectResponse;
public function unlock(Request $request, AuthContext $context): RedirectResponse|JsonResponse;
```

- `lock()` stores the locked flag in the session under `Lock::sessionKey($guard)`, saves the previous URL as the intended redirect, and redirects to the `unlock` route.
- `view()` renders the `unlock` view (passing the current `user` and `context`) when the locked flag is set, otherwise redirects home.
- `unlock()` validates `password`, re-checks it with `Hash::check` against the still-authenticated user, clears the locked flag, and redirects to the intended URL — or redirects back with an `auth.password` error.

The locked flag is enforced on protected routes by the `Redot\Auth\Middleware\Locked` middleware, which `AuthContext::auth()` appends automatically when the `lock-screen` feature is enabled.

## Gotchas

- Identifier input naming is dynamic: with a single identifier the field is that column name (e.g. `email`), but with two or more configured identifiers the request field becomes `identifier`. Build your forms with `$context->identifierInputName()`.
- API vs session behavior is decided entirely by `$context->api` — the same action emits Sanctum tokens or starts a session accordingly.
- Rate limiting keys are built from the lowercased identifier value plus the request IP; magic-link throttling uses a separate `magic-link` prefix and its own config keys.
- `touchLastLoginAt()` silently no-ops if the model has no `last_login_at` column.
- All static configuration (`Login::identifiers`, `Registration::createUserUsing`, `MagicLink::useNotificationClass`, etc.) is keyed by provider and should be registered in a service provider's `boot()` method, as the Dashboard app does in `AppServiceProvider`.

See also: [Auth Overview](/packages/auth/overview) and [Customizing Auth](/packages/auth/customization).
