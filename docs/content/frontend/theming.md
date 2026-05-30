# Theming

The dashboard's look — light/dark mode, accent color, gray palette, corner
radius, and body font — is theme-driven. Visitors can flip light/dark from the
navbar, and admins set the defaults from the settings screen. This page is about
how you switch and customize the theme; the script and stylesheet that apply it
are loaded for you by the layout.

## Switching light / dark

Any clickable element with a `data-theme` attribute toggles the color mode and
remembers the choice (per area) in the browser:

```blade
<a href="#" data-theme="dark">{{ __('Dark mode') }}</a>
<a href="#" data-theme="light">{{ __('Light mode') }}</a>
```

The dashboard and website navbars already ship these toggles, so you rarely add
them yourself. When the mode changes, a `theme:changed` event fires on
`document` — listen for it if a widget needs to restyle itself (the date picker
and rich editor already do).

## Customizing the defaults

The starting theme comes from the `theme` setting and is editable from the
dashboard settings screen. Each axis has a fixed set of values:

- **`primary`** — accent color. One of `blue`, `azure`, `indigo`, `purple`,
  `pink`, `red`, `orange`, `yellow`, `lime`, `green`, `teal`, `cyan`, `black`,
  or `inverted`.
- **`base`** — gray palette. `default`, `slate`, `gray`, `zinc`, `neutral`,
  `stone`, or `pink`.
- **`font`** — body font. `sans-serif`, `serif`, `monospace`, or `comic`.
- **`radius`** — corner radius scale. `0`, `0.5`, `1`, `1.5`, or `2`.

Change them server-side through the `theme` setting (e.g.
`setting('theme.primary')`) to move the defaults applied before any per-user
light/dark override.

```blade
<x-radios-colored name="theme[primary]" :title="__('Color scheme')" :value="setting('theme.primary')" :options="[
    'blue' => 'blue', 'azure' => 'azure', 'green' => 'green', /* … */
]" />
```

> Light/dark is the only axis a visitor controls (via the navbar toggle); the
> other four come from the `theme` setting and are changed through the settings
> form.

## Independent sidebar theme

The dashboard sidebar can stay dark while the rest of the page is light. The
`dashboard_sidebar_theme` setting controls it: `inherit` follows the page,
`dark` forces dark. It is exposed alongside the other theme controls on the
settings screen.

## Gotchas

- The dashboard and the public website remember light/dark **separately** — each
  area keeps its own stored choice.
- An unrecognized value for any axis simply applies no override and falls back to
  the underlying Tabler default.

## Related

- [Stylesheets](/frontend/stylesheets) — adding your own CSS on top of the theme.
- [Layouts](/layouts/overview)
- [Settings](/foundation/settings) — the `setting()` helper.
