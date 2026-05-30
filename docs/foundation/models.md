# Models

Redot Core ships a handful of Eloquent models that back its settings,
localization, and passwordless-auth features. Most of the time you interact with
them through the higher-level helpers and features documented elsewhere; this
page covers using the models directly.

## Setting

Reads and writes application settings. You usually read via the `setting()`
helper, but the model is available for writes and direct access:

```php
use Redot\Models\Setting;

Setting::set('app_name', ['en' => 'My App']);
$primary = Setting::get('theme.primary');
```

See [Settings](/foundation/settings) for the full reference — schema, defaults,
validation, and the `setting()` helper.

## Language

Represents an available locale (its code, display name, and text direction).

```php
use Redot\Models\Language;

$current = Language::current();      // the language for the active locale
$direction = $current->direction;    // 'rtl' or 'ltr'
$current->tokens;                    // its translation entries
```

Route-model binding resolves a language by its locale code, so a route parameter
like `/languages/ar/...` loads the Arabic language.

```php
Language::create([
    'code'   => 'fr',
    'name'   => 'Français',
    'is_rtl' => false,
]);
```

## LanguageToken

A single translation entry (key/value) belonging to a language. These are
populated and maintained by the localization tooling. Compose its scopes to query
by state:

- **`published()` / `unpublished()`** — written back to disk, or not yet.
- **`modified()` / `notModified()`** — value differs from the extracted original.
- **`fromJson()` / `notFromJson()`** — from the JSON catalog vs. PHP language
  files.

Editing a token's value automatically marks it unpublished until it is
re-published.

```php
$language->tokens()->fromJson()->pluck('value', 'key');
$language->tokens()->unpublished()->modified()->get();
```

See [Localization](/foundation/localization) for the workflow.

## LoginToken

Backs passwordless sign-in (magic link + one-time code). Each token targets an
email under a guard and expires after a configurable window.

```php
use Redot\Models\LoginToken;

// Issue a fresh token (replaces any prior token for the same email + guard).
$token = LoginToken::generate($user->email, 'admins');

// Look one up — only non-expired, matching-guard tokens are returned.
LoginToken::findByToken($token->token, 'admins');
LoginToken::findByCode($code, $user->email, 'admins');
```

Issuing a token replaces any existing one for the same email and guard. The
expiry window comes from `config('auth.magic_link.expire')` (default 15 minutes).
See [Authentication](/packages/auth/overview) for the full flow.

## Related

- [Settings](/foundation/settings)
- [Localization](/foundation/localization)
- [Notifications](/foundation/notifications) — the magic-link email that carries a
  `LoginToken`.
