# Theming

The Redot Dashboard theming system layers a small `themer.js` bootstrap script and a `themer.css` token sheet on top of Tabler/Bootstrap. It controls the color mode (light/dark), the gray palette base, the primary accent color, the corner radius, and the body font by writing `data-bs-theme*` attributes on `<html>`, with per-user overrides persisted in `localStorage`.

## How it fits together

Theming is wired through three pieces:

1. The `@themer` Blade directive (registered by `redot/core` in `RedotServiceProvider`) — injects the bootstrap script and the server-side theme config into the page head.
2. `public/assets/js/themer.js` — reads the config, applies attributes before paint, and binds the light/dark toggle.
3. `public/assets/css/themer.css` — declares the CSS custom properties for every `base`, `primary`, `radius`, and `font` value.

The CSS file is loaded once in the scaffold layout:

```blade
{{-- resources/layouts/scaffold.blade.php --}}
<link rel="stylesheet" href="{{ hashed_asset("/assets/css/themer.css") }}" />
```

## The `@themer` directive

`@themer` is provided by the core package and takes one optional argument — the **storage key** under which this layout persists theme choices. It pushes the bootstrap script onto the `pre-content` stack so the attributes are applied as early as possible.

```php
// vendor/redot/core/src/RedotServiceProvider.php
Blade::directive('themer', function ($expression = 'theme') {
    $path = hashed_asset('assets/js/themer.js');
    $expression = str_replace(['"', "'", '`'], '', $expression);
    $config = Js::encode(setting('theme'));

    return Blade::compileString(<<<EOT
        @push('pre-content')
            <script>window.themerKey = '$expression';</script>
            <script>window.themeConfig = $config;</script>
            <script src="$path"></script>
        @endpush
    EOT);
});
```

It emits two globals before loading the script:

- `window.themerKey` — the prefix for `localStorage` keys (the directive argument).
- `window.themeConfig` — the JSON-encoded value of the `theme` setting (`setting('theme')`).

Each layout invokes it with its own key so the dashboard and the public website keep independent theme storage:

```blade
{{-- resources/layouts/dashboard/base.blade.php --}}
@themer('dashboard-theme')

{{-- resources/layouts/dashboard/auth.blade.php --}}
@themer('dashboard-theme')

{{-- resources/layouts/website/base.blade.php & error.blade.php --}}
@themer('website-theme')
```

## `themer.js` behavior

`themer.js` is a self-invoking script. It builds an effective config by merging defaults with `window.themeConfig`:

```js
window.themerKey = window.themerKey || 'theme';
window.themeConfig = window.themeConfig || {};

const config = Object.assign(
    {
        theme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
        base: 'default',
        font: 'sans-serif',
        primary: 'blue',
        radius: 1,
    },
    window.themeConfig,
);
```

### Keys, defaults, and attribute mapping

For every key it checks `localStorage` for `${themerKey}-${key}`; if present that wins, otherwise the config value is used. It then writes an attribute on `document.documentElement` (`<html>`):

| Config key | Default | localStorage key | Attribute written |
| ---------- | ------- | ---------------- | ----------------- |
| `theme`    | OS `prefers-color-scheme` (`dark`/`light`) | `${themerKey}-theme`   | `data-bs-theme` |
| `base`     | `default` | `${themerKey}-base`    | `data-bs-theme-base` |
| `font`     | `sans-serif` | `${themerKey}-font`    | `data-bs-theme-font` |
| `primary`  | `blue` | `${themerKey}-primary` | `data-bs-theme-primary` |
| `radius`   | `1`    | `${themerKey}-radius`  | `data-bs-theme-radius` |

> The `theme` key alone maps to `data-bs-theme`; every other key maps to `data-bs-theme-<key>`.

### Light/dark toggle

On `DOMContentLoaded`, the script binds a `click` handler to every element with a `data-theme` attribute. Clicking it sets `data-bs-theme` to the attribute value, persists it under `${themerKey}-theme`, and dispatches a `theme:changed` event on `document`:

```js
document.querySelectorAll('[data-theme]').forEach((element) => {
    element.addEventListener('click', (event) => {
        event.preventDefault();
        let theme = element.dataset.theme;
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(`${themerKey}-theme`, theme);
        document.dispatchEvent(new CustomEvent('theme:changed'));
    });
});
```

The navbar partials provide these toggle triggers (the `hide-theme-*` classes hide whichever link does not match the active mode):

```blade
{{-- resources/layouts/dashboard/partials/navbar.blade.php --}}
<a href="#" data-theme="dark" class="nav-link hide-theme-dark">
    <span class="nav-link-icon"><i class="fa fa-moon"></i></span>
    <span class="nav-link-title">{{ __('Dark mode') }}</span>
</a>
<a href="#" data-theme="light" class="nav-link hide-theme-light">
    <span class="nav-link-icon"><i class="fa fa-sun"></i></span>
    <span class="nav-link-title">{{ __('Light mode') }}</span>
</a>
```

The same `data-theme="dark"` / `data-theme="light"` pattern is used in the website navbar (`resources/layouts/website/partials/navbar.blade.php`).

> Note: only the `theme` (light/dark) value is persisted by the click handler. The other axes (`base`, `font`, `primary`, `radius`) are driven from the server `theme` setting; they are only persisted to `localStorage` if something writes those keys.

## CSS variables in `themer.css`

`themer.css` is a flat set of attribute-selector rules. It does not define defaults on `:root`; each rule targets a specific attribute value.

### Base (gray palette) — `data-bs-theme-base`

Each base sets the full `--tblr-gray-50` … `--tblr-gray-950` ramp. Available values: `default`, `slate`, `gray`, `zinc`, `neutral`, `stone`, `pink`. The `default` base additionally redefines dark-mode surface tokens when combined with `data-bs-theme='dark'`:

```css
[data-bs-theme-base='default'][data-bs-theme='dark'] {
    --tblr-body-bg: #151f2c;
    --tblr-border-color: #2a3948;
    --tblr-bg-forms: #151f2c;
    --tblr-bg-surface: #182433;
    --tblr-bg-surface-secondary: #1b293a;
    --tblr-bg-surface-tertiary: #151f2c;
}
```

### Primary color — `data-bs-theme-primary`

Each value sets `--tblr-primary` and `--tblr-primary-rgb`. Values: `blue` (`#066fd1`), `azure`, `indigo`, `purple`, `pink`, `red`, `orange`, `yellow`, `lime`, `green`, `teal`, `cyan`, `black`, plus a special `inverted` variant that maps primary to `--tblr-gray-800` (and flips to `#f9fafb` under dark mode).

### Radius — `data-bs-theme-radius`

Sets `--tblr-border-radius-scale`. Values: `0`, `0.5`, `1`, `1.5`, `2`.

### Font — `data-bs-theme-font`

Sets `--tblr-body-font-family`. Values: `sans-serif`, `serif`, `monospace` (also forces `--tblr-body-font-size: 80%`), `comic`.

## The `theme` and `dashboard_sidebar_theme` settings

Both are defined in `config/redot.php` under settings:

```php
'dashboard_sidebar_theme' => [
    'default' => 'inherit',
],
'theme' => [
    'default' => [
        'primary' => 'blue',
        'base'    => 'default',
        'font'    => 'sans-serif',
        'radius'  => 1,
    ],
],
```

`setting('theme')` is what the `@themer` directive serializes into `window.themeConfig`, so changing it server-side changes the defaults applied before any `localStorage` override.

`dashboard_sidebar_theme` is independent of `themer.js`. The sidebar reads it directly as its own `data-bs-theme`, letting the sidebar stay dark even when the page is light:

```blade
{{-- resources/layouts/dashboard/partials/sidebar/base.blade.php --}}
<aside class="navbar navbar-vertical ... dashboard-sidebar"
    data-bs-theme="{{ setting('dashboard_sidebar_theme') }}" style="overflow: auto">
```

When the setting is `inherit`, the value is empty, so the sidebar inherits the page theme; `dark` forces dark.

## Theme settings UI

`resources/views/dashboard/settings/partials/theme-customizations.blade.php` renders the live theme editor. Each axis is a radio group whose `name` matches the `theme[...]` setting array, plus the separate sidebar control:

```blade
<x-radios-colored name="theme[primary]" :title="__('Color scheme')" :value="setting('theme.primary')" :options="[
    'blue' => 'blue', 'azure' => 'azure', 'indigo' => 'indigo', 'purple' => 'purple',
    'pink' => 'pink', 'red' => 'red', 'orange' => 'orange', 'yellow' => 'yellow',
    'lime' => 'lime', 'green' => 'green', 'teal' => 'teal', 'cyan' => 'cyan', 'black' => 'black',
]" />

<x-radios name="theme[base]" :title="__('Theme Base')" :value="setting('theme.base')" :inline="true" :options="[
    'default' => __('Default'), 'slate' => __('Slate'), 'gray' => __('Gray'),
    'zinc' => __('Zinc'), 'neutral' => __('Neutral'), 'stone' => __('Stone'), 'pink' => __('Pink'),
]" />

<x-radios name="theme[font]" :title="__('Font Family')" :value="setting('theme.font')" :inline="true" :options="[
    'sans-serif' => __('Sans Serif'), 'serif' => __('Serif'),
    'monospace' => __('Monospace'), 'comic' => __('Comic'),
]" />

<x-radios name="theme[radius]" :title="__('Corner Radius')" :value="setting('theme.radius')" :inline="true" :options="[
    '0' => __('None'), '0.5' => __('Small'), '1' => __('Medium'),
    '1.5' => __('Large'), '2' => __('Extra Large'),
]" />

<x-radios name="dashboard_sidebar_theme" :title="__('Sidebar theme')" :value="setting('dashboard_sidebar_theme', 'inherit')" :inline="true" :options="[
    'inherit' => __('Inherit'),
    'dark' => __('Force dark'),
]" />
```

The accompanying inline script gives a live preview by writing the corresponding `data-bs-theme-*` attribute on `<html>` as the radios change, and syncing the sidebar:

```js
$('[name^="theme"]').on('change', function () {
    if (this.checked === false) return;
    const key = this.name.replace('theme[', '').replace(']', '');
    document.documentElement.setAttribute(`data-bs-theme-${key}`, this.value);
});

$('[name="dashboard_sidebar_theme"]').on('change', function () {
    if (this.checked === false) return;
    const value = this.value;
    const theme = value === 'inherit' ? $('html').attr('data-bs-theme') : value;
    $('.dashboard-sidebar').attr('data-bs-theme', theme);
});

$('[type="reset"]').on('click', function () {
    setTimeout(() => $('[name^="theme"], [name="dashboard_sidebar_theme"]').trigger('change'), 0);
});
```

> The live preview writes attributes directly to the DOM; the actual values are persisted by submitting the settings form (which updates the `theme` / `dashboard_sidebar_theme` settings). This preview path does not write `localStorage`.

## Gotchas

- The `@themer` argument is the `localStorage` prefix, not a theme name. Dashboard layouts use `dashboard-theme`; website layouts use `website-theme`, so the two areas remember theme choices independently.
- Only light/dark (`theme`) is persisted by the navbar toggle. `base`, `primary`, `font`, and `radius` come from `setting('theme')` and are changed through the settings form, not the toggle.
- `themer.css` has no `:root` fallbacks; an unrecognized value for any axis simply matches no rule, leaving the underlying Tabler defaults.
- The first OS-driven default (`prefers-color-scheme`) only applies when neither `localStorage` nor `themeConfig` provides a `theme` value — note `theme` is not part of the server `theme` setting, so dark/light is purely client-driven.

## Related

- [Layouts](/layouts/overview)
- [Asset pipeline](/frontend/asset-system)
