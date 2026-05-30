# Turnstile Initializer

`turnstile` renders a [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/) captcha and writes the solved token into a hidden form input. It backs the [`<x-captcha>` component](/components/captcha) and follows the page locale and light/dark theme.

## Enable it

You don't enable this by hand — render the [`<x-captcha>` component](/components/captcha), which adds `init="turnstile"`, loads the Cloudflare script, and supplies the site key. Only render it when a site key is configured:

```blade
@if (setting('cloudflare_turnstile_site_key'))
    <x-captcha :title="__('Captcha')" name="captcha" />
@endif
```

Once solved, the token is submitted with the form and validated server-side with the `captcha` rule. See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

The site key, theme, and language are filled from the page automatically. Pass extra Turnstile rendering options as `captcha-` attributes (note: the prefix is `captcha-`, not `turnstile-`):

- **`captcha-action`** — an action label sent with the challenge.
- **`captcha-size`** — widget size (`flexible` by default).
- **`captcha-retry-interval`** — delay (ms) before automatically retrying a failed challenge.

```blade
<x-captcha name="captcha" captcha-action="login" />
```

## Related

- [Captcha component](/components/captcha) — the component that enables this for you.
- [Theming](/frontend/theming) — the light/dark mode the widget follows.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
