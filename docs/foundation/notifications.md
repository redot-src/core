# Notifications

Redot Core ships a small set of framework notifications used by its built-in features. The only one currently provided is `MagicLinkNotification`, the email that delivers a passwordless login link and one-time code to a user during magic-link authentication.

## MagicLinkNotification

`Redot\Notifications\MagicLinkNotification` is a standard Laravel `Illuminate\Notifications\Notification`. It is `Queueable`, so it respects queue configuration when the framework dispatches it asynchronously.

```php
namespace Redot\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Redot\Models\LoginToken;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LoginToken $loginToken,
        public string $verifyRoute,
    ) {}

    public function via(object $notifiable): array;       // ['mail']
    public function toMail(object $notifiable): MailMessage;
}
```

### Constructor

- `LoginToken $loginToken` — the persisted token record produced by `Redot\Models\LoginToken::generate()`. It exposes the `token` (a 64-char random string used in the link) and `code` (a 6-char one-time code shown in the body).
- `string $verifyRoute` — the **named route** that handles clicking the link. The notification calls `route($verifyRoute, ['token' => $this->loginToken->token])`, so this route must accept a `token` parameter.

Both are declared as promoted public properties, so they are also readable on the notification instance.

### Channels

`via()` returns `['mail']` only. The notification is delivered exclusively over the mail channel; there is no database, broadcast, or SMS representation.

### Mail content

`toMail()` builds a `MailMessage` with:

- **Subject:** `Your Login Code for :app`, where `:app` is `config('app.name')`.
- **Line:** an instruction containing the one-time code, rendered in bold: `Click the button below to log in, or use the code: **:code**` (`:code` = `$loginToken->code`).
- **Action button:** labelled `Login Now`, linking to `route($verifyRoute, ['token' => $loginToken->token])`.
- **Line:** `This link expires in :minutes minutes.`, where `:minutes` comes from `config('auth.magic_link.expire', 15)` (default 15).
- **Line:** `If you did not request this, you can safely ignore this email.`

All strings pass through `__()`, so they are translatable through the standard Laravel translation files.

## How it ties into the MagicLink auth action

The notification is sent by `Redot\Auth\Actions\MagicLink` (see [Auth](/packages/auth/overview)). The action holds the notification class in a swappable static property:

```php
protected static ?string $notificationClass = MagicLinkNotification::class;

public static function useNotificationClass(string $class): void
{
    static::$notificationClass = $class;
}
```

During `send()`, the action generates a token and notifies the user:

```php
$loginToken = $tokenModel::generate($user->email, $context->guard);

$user->notify(new $notificationClass(
    $loginToken,
    $context->routeName('magic-link-code.show'), // the verify route name
));
```

So `$verifyRoute` passed into the notification is the guard-scoped route name for `magic-link-code.show`, resolved from the current `AuthContext`. When the recipient clicks **Login Now**, that route receives the `token` query parameter and `MagicLink::verifyToken()` looks it up via `LoginToken::findByToken()`, deleting it and logging the user in. Alternatively, the user can enter the 6-character `code` from the email body, which is resolved by `MagicLink::verifyCode()` through `LoginToken::findByCode()`.

### The LoginToken model

`Redot\Models\LoginToken::generate(string $email, string $guard)` creates the record the notification carries. It deletes any prior tokens for the same email/guard, then stores a fresh `token` (`Str::random(64)`), `code` (`Str::random(6)`), and an `expires_at` of `now()->addMinutes(config('auth.magic_link.expire', 15))`. The same `expire` config value is what the email advertises as the expiry window, keeping the message and the stored token in sync.

## Config keys that affect the email

- `auth.magic_link.expire` — minutes until the token expires; default `15`. Drives both `LoginToken::generate()` and the "expires in :minutes minutes" line.
- `app.name` — interpolated into the subject line.

## Customizing the email

To replace the email entirely (different layout, extra channels, branding), register a custom notification class on the action, typically from a service provider's `boot()`:

```php
use Redot\Auth\Actions\MagicLink;
use App\Notifications\CustomMagicLink;

MagicLink::useNotificationClass(CustomMagicLink::class);
```

Your class is instantiated as `new $notificationClass($loginToken, $verifyRoute)`, so it must accept the same two constructor arguments. For smaller tweaks (wording, colors), publish and edit Laravel's mail notification templates and translation strings rather than swapping the class.
