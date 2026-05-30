# Captcha

`<x-captcha>` drops a [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/)
challenge into a form. It renders the widget plus a hidden field that receives
the verification token, and wires up the Turnstile JavaScript automatically.

## Usage

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

Always guard usage with the site-key check — the widget needs a configured site
key to render. The hidden token field is always required, so client-side
validation fails until the challenge is solved.

## Options

- **`title`** — label shown above the widget.
- **`name`** — name of the hidden field that holds the verification token
  (defaults to `captcha`).
- **`id`** — element id; auto-generated when omitted.

Turnstile options can be overridden per element with `captcha-*` attributes
(e.g. `captcha-theme="dark"`). Theme and language follow the dashboard's current
theme and the page language by default.

## Examples

### In a registration form

```blade
<x-form :action="route('website.register.store')" method="POST">
    <!-- other fields -->
    @if (setting('cloudflare_turnstile_site_key'))
        <x-captcha :title="__('Captcha')" name="captcha" />
    @endif

    <button type="submit" class="btn btn-primary">{{ __('Sign up') }}</button>
</x-form>
```

## Configuration

Set the Turnstile keys in dashboard settings under third-party services:

- **`cloudflare_turnstile_site_key`** — public key used by the widget.
- **`cloudflare_turnstile_secret_key`** — secret key used to validate the token
  server-side.

## Related

- [Form](/components/form) — the form the captcha lives in.
- [Components overview](/components/overview) — how JS-backed components initialize.
