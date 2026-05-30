# Validation Rules

Redot Core ships two custom validation rules you apply like any other Laravel
rule: `phone` validates that a value is a real, parseable phone number, and
`captcha` verifies a Cloudflare Turnstile token. Both are available as string
rule names or as rule objects.

## Usage

Apply them as string rules:

```php
$request->validate([
    'phone'   => 'required|phone',
    'captcha' => 'required|captcha',
]);
```

Or as rule objects:

```php
use Redot\Rules\Phone;
use Redot\Rules\Captcha;

$request->validate([
    'phone'   => ['required', new Phone],
    'captcha' => ['required', new Captcha],
]);
```

Define the messages in your `validation` language file:

```php
'phone'   => 'The :attribute field must be a valid phone number.',
'captcha' => 'Captcha verification failed.',
```

## Phone

Validates that a value parses as a valid phone number for a region (default
`EG`). Pass an ISO country code to change the region used when the number has no
international prefix:

```php
'us_phone' => 'required|phone:US',          // string form
'us_phone' => ['required', new Phone('US')], // object form
```

Once validated, normalize the number to E.164 with the
[`format_phone()`](/foundation/helpers) helper.

## Captcha

Verifies a Cloudflare Turnstile token against Cloudflare. Things to know:

- **Skipped outside production** — verification passes automatically when the app
  is not in production, so local and CI forms don't need a real token.
- **Needs the secret key** — reads `setting('cloudflare_turnstile_secret_key')`;
  verification fails if it is empty.
- **Field name must match** — the validated attribute name must match the field
  the Turnstile widget posts (`name="captcha"` below).

Render the widget with the `<x-captcha>` component and apply the rule only when a
site key is configured:

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

```php
// in your validation rules
...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
```

The Turnstile site and secret keys are stored as settings
(`cloudflare_turnstile_site_key`, `cloudflare_turnstile_secret_key`).

## Related

- [Helpers](/foundation/helpers) — `format_phone()` for normalizing numbers.
- [Settings](/foundation/settings) — where the Turnstile keys are stored.
