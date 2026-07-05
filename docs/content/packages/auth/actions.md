# Auth Actions

Each authentication flow registered by [Auth Routes](/packages/auth/routes) is backed by a self-contained "action" — login, two-factor authentication, logout, registration, magic link, password reset, email verification, and the lock screen. This page describes what each one does and the small hooks you can tune. To replace a flow wholesale, see [Customize auth](/packages/auth/customization).

Every action adapts to the guard automatically: a web guard starts a session and redirects, an API guard returns JSON and issues a bearer token. You build the forms once and they work for both.

## Login

Validates the credentials, throttles repeated attempts by identifier and IP, looks the user up, and checks the password. On success it logs the user in (honoring a `remember` checkbox) and redirects to the post-login destination, or returns a token for API guards. On failure it returns a validation error on the identifier field.

By default users log in with their `email`. You can allow extra identifiers and override the rules per provider — see [Customize auth](/packages/auth/customization#change-login-identifiers-and-rules).

## Two-factor authentication

Adds a second verification step to the login flow. Methods are pluggable — the package ships an authenticator app method (`totp`) and an email code method (`email`), and users can enable any combination. When a user with a confirmed method signs in, the login action stops before issuing a session or token and hands off to the challenge, where the user verifies with a method code or a single-use recovery code. Codes for deliverable methods (email, SMS, …) are sent automatically at that hand-off, so the challenge screen never asks the user to request the first code — the per-method send endpoint remains for resends. After `auth.two_factor.max_attempts` failed attempts (default 5) the challenge is invalidated and the user must log in again.

On web guards the challenge is a screen; on API guards login returns `{ two_factor: true, challenge_token }` and the client exchanges the token plus a code at the challenge endpoint for a bearer token. Management endpoints are generic per method (`two-factor/{method}`, confirm, delete): enabling starts the method's setup (the `totp` method returns a secret and otpauth URL, deliverable methods send a confirmation code), confirming activates it, and disabling an enabled method or regenerating recovery codes requires the current password. Recovery codes are created when the first method is confirmed, returned exactly once (flashed to the session on web, in the JSON payload on API), and removed when the last method is disabled. Secrets and recovery codes are encrypted at rest.

The guard's model must use the `Redot\Auth\Concerns\TwoFactorAuthenticatable` trait. One-time codes expire after `auth.two_factor.expire` minutes (default 10). To add your own method (SMS, WhatsApp, …), see [Customize auth](/packages/auth/customization#add-a-two-factor-method).

## Logout

Ends the session and redirects home for web guards, or revokes the current access token and returns a success message for API guards. The logout route stays reachable even when the user is locked, so a locked user can always sign out.

## Registration

Validates the request, creates the user, and fires Laravel's `Registered` event (which kicks off email verification when your model requires it). Web guards are logged in and redirected; API guards get a token and a `201`.

Out of the box it requires a unique `email` and a confirmed `password`. To capture more fields (name, a captcha, …) override the registration rules and the user-creation step per provider — see [Customize auth](/packages/auth/customization#customize-registration).

## Magic link

Passwordless login. The user requests a link by entering their identifier, receives an email containing both a clickable link and a short code, and authenticates with either one. Requests are throttled, expired tokens are rejected with a friendly error, and the consumed token is deleted on success.

You can point the flow at your own login-token model or your own notification class — see [Customize auth](/packages/auth/customization). Magic links are web-only and are skipped for API guards.

## Password reset

Wraps Laravel's password broker for the guard. The "forgot password" screen emails a reset link; the reset screen validates the token, email, and a confirmed new password, then resets it and fires the `PasswordReset` event. Web guards land back on the login screen with a success message; API guards get the status as JSON.

## Email verification

Shows the "verify your email" prompt, handles the signed verification link, and resends the notification on request (rate-limited). Already-verified users are sent home. These routes run behind the auth middleware, so they apply to the signed-in user.

## Lock screen

Lets a signed-in user lock their session and unlock it later with their password — useful for shared machines. Locking redirects to the unlock screen; unlocking re-checks the password and returns the user to where they were. The lock screen is session-only: it is available only when you provide an `unlock` view on a web guard, and is skipped for API guards. To change how unlocking works (for example a PIN instead of a password), see [Customize auth](/packages/auth/customization).

## Related

- [Auth Overview](/packages/auth/overview)
- [Register auth routes](/packages/auth/routes)
- [Customize auth](/packages/auth/customization)
