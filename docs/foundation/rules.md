# Validation Rules

Redot Core ships two custom validation rules — `Phone` and `Captcha` — that implement Laravel's `ValidationRule` contract. Both are also registered as named validator extensions (`phone` and `captcha`) at boot time, so you can use them either as rule objects or as string rule names. A companion `format_phone()` helper normalizes phone numbers to E.164.

## Key concepts

Both rules live in the `Redot\Rules` namespace and implement `Illuminate\Contracts\Validation\ValidationRule`. Each also exposes a public `passes(string $attribute, mixed $value): bool` method in addition to the contract's `validate()` method — the service provider uses `passes()` when wiring up the string-name extensions.

The `RedotServiceProvider::configureValidationRules()` method runs in `boot()` and registers both rules as string-name extensions:

```php
Validator::extend('phone', function ($attribute, $value, $parameters) {
    return (new Phone(...$parameters))->passes($attribute, $value);
});

Validator::extend('captcha', function ($attribute, $value, $parameters) {
    return (new Captcha(...$parameters))->passes($attribute, $value);
});
```

Because the rules are spread (`...$parameters`) into the constructor, any extra rule-string parameters become constructor arguments. For `phone` that means the country code can be passed inline (e.g. `phone:US`). `captcha` takes no constructor arguments.

## `Redot\Rules\Phone`

Validates that a value is a parseable, valid phone number using `libphonenumber\PhoneNumberUtil`.

```php
public function __construct(protected $country = 'EG')
public function validate(string $attribute, mixed $value, Closure $fail): void
public function passes(string $attribute, mixed $value): bool
```

- The constructor defaults the parsing country to `EG`. Pass another ISO country code to change the default region used when the number has no international prefix.
- `passes()` parses the value with the given country and returns `$instance->isValidNumber($phone)`. If `libphonenumber` throws a `NumberParseException`, it returns `false`.
- On failure, `validate()` calls `$fail('validation.phone')->translate()`, so the message comes from your `validation.phone` translation key.

### Usage

As a rule object:

```php
use Redot\Rules\Phone;

$request->validate([
    'phone' => ['required', new Phone],          // defaults to EG
    'us_phone' => ['required', new Phone('US')], // override the country
]);
```

As a string rule (registered extension):

```php
$request->validate([
    'phone' => 'required|phone',
    'us_phone' => 'required|phone:US', // parameter is spread into the constructor
]);
```

You must define the message key in `lang/{locale}/validation.php`. The consumer app provides:

```php
// lang/en/validation.php
'phone' => 'The :attribute field must be a valid phone number.',
```

## `Redot\Rules\Captcha`

Verifies a Cloudflare Turnstile token against Cloudflare's siteverify endpoint.

```php
public function validate(string $attribute, mixed $value, Closure $fail): void
public function passes(string $attribute, mixed $value): bool
```

Behavior of the internal `verifyCloudflareResponse($token)`:

- **Non-production short-circuit:** if `! app()->isProduction()`, it returns `true` immediately. CAPTCHA verification is effectively skipped outside production.
- It reads the secret via `setting('cloudflare_turnstile_secret_key')`. If the secret is empty, verification fails (`false`).
- Otherwise it POSTs `secret` and `response` (the token) to `https://challenges.cloudflare.com/turnstile/v0/siteverify` and returns the response's `success` field.
- On failure, `validate()` calls `$fail(__('validation.captcha'))`.

Unlike `Phone`, `Captcha` has no constructor arguments — `captcha` (with parameters) would just pass extra args that the constructor ignores.

### Usage

The dashboard app applies the `captcha` rule conditionally on registration, only when a Turnstile site key is configured:

```php
// app/Providers/AppServiceProvider.php
Registration::validationRules('users', fn (AuthContext $context) => [
    'first_name' => ['required', 'string', 'max:255'],
    'last_name'  => ['required', 'string', 'max:255'],
    'email'      => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
    'password'   => ['required', 'confirmed', Password::defaults()],
    ...setting('cloudflare_turnstile_site_key') ? ['captcha' => ['required', 'captcha']] : [],
]);
```

As a rule object:

```php
use Redot\Rules\Captcha;

$request->validate([
    'captcha' => ['required', new Captcha],
]);
```

The corresponding message key:

```php
// lang/en/validation.php
'captcha' => 'Captcha verification failed.',
```

On the front end, the dashboard renders the Turnstile widget with an `<x-captcha>` component, gated on the site key setting:

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

The token field name (`name="captcha"`) must match the validated attribute. The site/secret keys are stored as settings (`cloudflare_turnstile_site_key`, `cloudflare_turnstile_secret_key`), both defaulting to an empty string in `config/redot.php`. See [Settings](/foundation/settings) for how the `setting()` helper resolves these.

## `format_phone()` helper

Defined in `src/helpers.php`, this formats a phone number to E.164 using `libphonenumber`:

```php
function format_phone(string $phone, string $country = 'EG'): string
```

It parses `$phone` for the given `$country` region and returns the number formatted as `PhoneNumberFormat::E164`.

```php
format_phone('01012345678');        // "+201012345678" (EG default)
format_phone('2025550123', 'US');   // "+12025550123"
```

> **Gotcha:** `format_phone()` does not catch `NumberParseException`. Validate the input first (with the `Phone` rule) before formatting, or wrap the call in a try/catch — an unparseable string will throw.

## Related

- [Helpers](/foundation/helpers) — full list of global helper functions including `format_phone()`.
- [Settings](/foundation/settings) — the `setting()` helper used by the Captcha rule.
