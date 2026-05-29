# Turnstile Initializer

The `turnstile` initializer renders a [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/) captcha widget into an element and wires the resulting token back into a hidden form input. It is the JavaScript behind the [`<x-captcha>`](/components/captcha) Blade component.

## What it is

The script lives at `public/assets/inits/turnstile.js`. Like every file under `public/assets/inits/`, it is a module that **returns** a function. That function is registered on the global `window.__inits` registry under the key `turnstile`, and is invoked automatically by the platform's `init()` dispatcher (in `public/assets/js/functions.js`) for any element carrying the `init="turnstile"` attribute.

The exported initiator has the signature:

```js
(selector, options = {}) => { /* ... */ }
```

- `selector` — the DOM element to initialize (the `init()` dispatcher passes the matched element itself).
- `options` — an optional object merged on top of the computed defaults.

It wraps the global `turnstile` object loaded from Cloudflare's `api.js?render=explicit` script (injected by the captcha component, see below).

## Default options

The initiator builds its options by merging three sources in this order (later sources win), using Lodash `_.merge`:

1. **`defaultOptions`** computed from the page:

   | Option | Default value | Source |
   | --- | --- | --- |
   | `sitekey` | content of `<meta name="cloudflare-turnstile-site-key">` | meta tag rendered by the component |
   | `theme` | `localStorage.getItem(window.themerKey)` or `'light'` | current themer theme (light/dark) |
   | `language` | the `lang` attribute of `<html>` | page locale, drives RTL/translations |
   | `size` | `'flexible'` | fixed default |

2. **Selector options** — any attributes on the element prefixed with `captcha-`, parsed by `getOptionsFromSelector(selector, 'captcha-')`. The prefix is stripped and the remaining key is camel-cased (e.g. `captcha-action="login"` becomes `{ action: 'login' }`, `captcha-retry-interval="3000"` becomes `{ retryInterval: 3000 }`). Values are coerced to primitives.

3. **`options`** — the object passed directly to the initiator (rarely used through the auto-init path).

After merging, the initiator overrides `options.callback` with a function that writes the issued token into the element's child `<input>`:

```js
options.callback = function (token) {
    $(selector).find('input').val(token);
};
```

It then renders the widget and stores the Turnstile widget instance id on the element via jQuery data:

```js
const instance = turnstile.render(selector, options);
$(selector).data('captcha', instance);
```

### Locale / RTL handling

The `language` option is taken straight from `<html lang="...">`. Cloudflare Turnstile uses this to localize the widget and to render right-to-left when an RTL locale is supplied, so the captcha automatically follows the page language with no extra configuration.

### Theme handling

The `theme` option reads the current value stored under `window.themerKey` in `localStorage`, falling back to `'light'`. This keeps the captcha visually consistent with the dashboard's light/dark theme.

## How it is triggered

You normally never call this initiator directly. Use the [`<x-captcha>` component](/components/captcha), whose PHP class adds the `cf-turnstile` class and the `init="turnstile"` attribute to its root element:

```php
$attributes->class(['cf-turnstile'])->merge([
    'init' => 'turnstile',
]);
```

When the platform's `init()` runs, it finds `[init]:not([initialized])` elements, looks up `window.__inits['turnstile']`, and calls it with the element and any options parsed from an `turnstile="..."` attribute. The component also injects the required Cloudflare script and the site-key meta tag:

```blade
@pushOnce('meta', 'cloudflare-turnstile-meta')
    <meta name="cloudflare-turnstile-site-key" content="{{ setting('cloudflare_turnstile_site_key') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'cloudflare-turnstile-scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
@endPushOnce
```

## Usage

Real usage from the registration form (`resources/views/website/auth/register.blade.php`). The captcha is only rendered when a Turnstile site key is configured in settings:

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

The rendered markup looks like the following, which the initiator picks up automatically:

```blade
<div id="captcha-..." class="cf-turnstile" init="turnstile">
    <input type="hidden" name="captcha" validation="required">
</div>
```

Once the user solves the challenge, the issued token is written into the hidden `input[name="captcha"]` and submitted with the form. Server-side, the value is validated with the `captcha` rule (registered in `AppServiceProvider` when `cloudflare_turnstile_site_key` is set).

## Gotchas

- **No site key, no widget.** If the `cloudflare-turnstile-site-key` meta tag is missing or empty, `sitekey` is undefined and `turnstile.render` will fail. The component guards this by only rendering when the setting exists.
- **The Cloudflare script must load first.** The widget relies on the global `turnstile` object from `api.js?render=explicit`. The component pushes that script via `@pushOnce`, so use the component rather than the raw initiator.
- **One hidden input expected.** The success callback targets `$(selector).find('input')` and sets its value, so the element must contain exactly one input (as the component provides).
- **Selector options use the `captcha-` prefix**, not `turnstile-`. Any extra Turnstile rendering option can be passed by adding a `captcha-*` attribute to the element.

## Related

- [Captcha component](/components/captcha) — the Blade component that emits the `init="turnstile"` element and loads the Cloudflare script.
