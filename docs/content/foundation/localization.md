# Localization

Redot Core resolves the active locale on every request, persists it across the
session, and keeps URLs in sync — so the right language is served and links carry
the locale automatically. Translation strings are managed in the dashboard and
published back to your Laravel language files.

## How locale resolution works

The localization layer runs automatically on every web request; you do not wire
it up. For each request it:

1. Decides the **scope** — `dashboard` for dashboard routes, `website` otherwise.
   Each scope has its own allowed-locale list (`dashboard_locales` /
   `website_locales` settings) and its own session key.
2. Picks the locale from the first available of: `?locale=` query string → the
   locale URL segment → the session → the browser's `Accept-Language`.
3. Falls back to the first allowed locale if the choice is empty or not allowed.
4. Applies it, stores it in the session, and makes generated URLs carry it
   automatically — so `route()` calls do not need a `locale` argument.
5. If the URL carried a different locale than the one resolved, redirects to the
   corrected URL.

Because the allowed locales come from settings (not config), changing
`website_locales` / `dashboard_locales` takes effect immediately. See
[Settings](/foundation/settings).

## Configuration

The relevant keys live in `config/redot.php`:

```php
// Seed locales for a fresh install.
'locales' => [
    ['code' => 'en', 'name' => 'English', 'is_rtl' => false],
    ['code' => 'ar', 'name' => 'العربية', 'is_rtl' => true],
],

'routing' => [
    'append_locale_to_url'     => true, // prefix web routes with /{locale}
    'redirect_non_locale_urls' => true, // redirect non-prefixed URLs to the prefixed version
],
```

The available codes and display names are exposed at runtime as
`config('app.locales')` (a `code => name` map), populated from the languages you
have configured.

## Usage

### A locale switcher

Render links that set `?locale=`; the request layer reads and remembers the
choice:

```blade
@if (count(setting('dashboard_locales')) > 1)
    @foreach (setting('dashboard_locales') as $locale)
        <a href="{{ url()->current() }}?locale={{ $locale }}">
            {{ config('app.locales.' . $locale) }}
        </a>
    @endforeach
@endif
```

### Document direction

Resolve the current language's text direction for a layout:

```php
use Redot\Models\Language;

$direction = Language::current()->direction; // 'rtl' for Arabic, 'ltr' otherwise
```

### Querying translation tokens

Translation entries expose convenience scopes you can compose. Use them to drive
the token-management UI or your own reporting:

- **`published()` / `unpublished()`** — entries written back to disk, or not yet.
- **`modified()` / `notModified()`** — entries whose value differs from the
  original it was extracted with.
- **`fromJson()` / `notFromJson()`** — entries from the JSON catalog vs. PHP
  language files.

```php
use Redot\Models\LanguageToken;

LanguageToken::published()->fromJson()->get();
LanguageToken::unpublished()->modified()->count();
```

## Notes

- Editing a translation value un-publishes it — re-publish after edits.
- `Language::current()` resolves the language matching the active locale, so make
  sure your locales are seeded.
- With `append_locale_to_url` off, locale comes from query/session/browser only
  and there is no URL prefix or redirect.

## Related

- [Settings](/foundation/settings) — the allowed-locale settings.
- [Lang Extractor](/packages/lang-extractor) — extracting and publishing tokens.
