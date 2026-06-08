# Notifications

Redot Core ships the email that delivers a passwordless login link and one-time
code to a user during magic-link authentication. It is sent for you by the
magic-link auth flow — you only need to touch it when you want to customize the
email.

## How it's used

When a user requests a magic link, the auth flow generates a login token and
emails it. The message contains a **Login Now** button (the link) and a
6-character **one-time code** the user can type instead. Both expire after the
configured window. You don't dispatch this notification yourself; the auth flow
does. See [Authentication](/packages/auth/overview).

## Customizing the email

For wording, colors, or translations, edit the relevant translation strings and
the published mail notification templates — the email text passes through `__()`,
so it is fully translatable.

To replace the email entirely (different layout, extra channels, branding),
register your own notification class on the magic-link action, typically from a
service provider's `boot()`:

```php
use Redot\Auth\Actions\MagicLink;
use App\Notifications\CustomMagicLink;

MagicLink::useNotificationClass(CustomMagicLink::class);
```

Your class is instantiated with the same two arguments the default receives — the
login token and the name of the verify route — so accept those when you build a
replacement.

## Options

Config keys that affect the email:

- **`auth.magic_link.expire`** — minutes until the token expires (default `15`).
  Drives both the token lifetime and the "expires in :minutes minutes" line.
- **`app.name`** — interpolated into the subject line.

## Related

- [Authentication](/packages/auth/overview) — the magic-link flow that sends it.
- [Dashboard Notifications](/foundation/dashboard-notifications) — in-app
  notifications shown in the dashboard bell and notifications page.
- [Models](/foundation/models) — the login token the email carries.
