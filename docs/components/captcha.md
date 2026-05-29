# Captcha

The `<x-captcha>` component renders a [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) widget inside a form. It outputs a labelled container plus a hidden input that receives the verification token, and wires up the Turnstile JavaScript automatically.

## What it is

`<x-captcha>` is a class-based Blade component backed by `App\View\Components\Captcha` (extends `App\View\Components\Component`), rendering the view `resources/components/captcha.blade.php`.

On render the component:

- Generates an `id` if none was given (`uniqid('captcha-')`).
- Pushes the Turnstile site key (`setting('cloudflare_turnstile_site_key')`) into a `<meta>` tag (once per page, stack `meta`).
- Adds the `cf-turnstile` CSS class and the `init="turnstile"` attribute to its root `<div>`, which tells the asset system to bootstrap the Turnstile JS init on that element.
- Loads the Cloudflare Turnstile script (`?render=explicit`) once per page (stack `plugins-scripts`).
- Emits a hidden <span v-pre>`<input name="{{ $name }}" validation="required">`</span> that the JS fills with the token after a successful challenge.

## Props

From the constructor of `App\View\Components\Captcha`:

| Prop    | Type           | Default          | Description |
|---------|----------------|------------------|-------------|
| `id`    | `?string`      | `uniqid('captcha-')` | Element id of the root `<div>` and the `for` target of the label. Auto-generated when omitted. |
| `title` | `?string`      | `null`           | Label text. When set, a `<x-label :title :for="$id" :required="true" />` is rendered above the widget. |
| `hint`  | `?string`      | `null`           | Accepted by the constructor (reserved); not referenced in the current view. |
| `name`  | `string`       | `'captcha'`      | `name` of the hidden input that holds the Turnstile token. |

Any extra HTML attributes pass through the `$attributes` bag onto the root `<div>` (merged with `class="cf-turnstile"` and `init="turnstile"`).

The hidden input always carries `validation="required"`, so client-side validation fails until the challenge is solved.

## JS init

The root `<div>` is tagged `init="turnstile"`, handled by `public/assets/inits/turnstile.js`. It calls `turnstile.render(selector, options)` and, on success, writes the token into the container's hidden `<input>` and stores the widget instance via `$(selector).data('captcha', instance)`.

Default options resolved by the init:

- `sitekey` — from the `cloudflare-turnstile-site-key` `<meta>` tag.
- `theme` — current themer value from `localStorage` (`window.themerKey`), falling back to `'light'`.
- `language` — the `<html lang>` attribute.
- `size` — `'flexible'`.

Options can be overridden per element with `captcha-`-prefixed data attributes (read via `getOptionsFromSelector(selector, 'captcha-')`), e.g. `data-captcha-theme="dark"`.

## Usage

Real usage from the registration form (`resources/views/website/auth/register.blade.php`), guarded by the presence of a configured site key:

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

Minimal form embed:

```blade
<x-form :action="route('website.register.store')" method="POST">
    <!-- other fields -->
    <x-captcha :title="__('Captcha')" name="captcha" />

    <button type="submit" class="btn btn-primary">{{ __('Sign up') }}</button>
</x-form>
```

## Configuration

The Turnstile keys are configured in dashboard settings (`resources/views/dashboard/settings/partials/3rd-party-services.blade.php`):

- `cloudflare_turnstile_site_key` — used by the component / meta tag (public).
- `cloudflare_turnstile_secret_key` — used server-side to validate the token.

## Gotchas

- The widget renders nothing useful without `cloudflare_turnstile_site_key` set; always guard usage with `@if (setting('cloudflare_turnstile_site_key'))` as the register page does.
- The `hint` prop is accepted but unused in the current view.
- The Turnstile script and meta tag use `@pushOnce`, so multiple `<x-captcha>` instances on one page won't duplicate them.

## Related

- [Form component](/components/form)
- [Label component](/components/label)
- [Input component](/components/input)
