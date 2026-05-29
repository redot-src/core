# Logo

`<x-logo>` renders the application logo. It outputs two `<img>` tags — one for the dark theme and one for the light theme — and lets the active Tabler theme decide which one is visible, so the logo automatically adapts to light/dark mode.

## What it is

An **anonymous Blade component** (no PHP class). The view lives at `resources/components/logo.blade.php` and declares a single `@props`:

```blade
@props([
    'lazy' => true,
])

<img src="{{ setting('app_logo_dark') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
    {{ $attributes->class(['hide-theme-light']) }} />
<img src="{{ setting('app_logo_light') }}" alt="{{ app_name() }}" loading="{{ $lazy ? 'lazy' : 'eager' }}"
    {{ $attributes->class(['hide-theme-dark']) }} />
```

Both image sources are pulled from application settings via the `setting()` helper, and the `alt` text comes from `app_name()`.

## Props

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `lazy` | bool | `true` | Controls the native `loading` attribute on both images. `true` renders `loading="lazy"`; `false` renders `loading="eager"`. |

There are no other declared props. There is no slot and no `wire:model` / Livewire binding.

## Attributes and theme behavior

Any additional HTML attributes are forwarded to **both** `<img>` tags via `$attributes`.

- Extra `class` values are merged onto both images using `$attributes->class([...])`, **in addition to** the built-in theme class each image always carries:
  - The dark-theme logo (`setting('app_logo_dark')`) always gets `hide-theme-light`.
  - The light-theme logo (`setting('app_logo_light')`) always gets `hide-theme-dark`.
- `hide-theme-light` / `hide-theme-dark` are Tabler theme utility classes. They hide the element when the corresponding theme is active, so only one of the two images is shown at a time. The active theme is toggled by the theme switcher links in the navbars (`data-theme="dark"` / `data-theme="light"`), and the visibility rules are reinforced in `public/assets/css/overrides.css`.

## Image sources

| Image | Source setting | Shown when |
| --- | --- | --- |
| Dark logo | `setting('app_logo_dark')` | Dark theme active (carries `hide-theme-light`) |
| Light logo | `setting('app_logo_light')` | Light theme active (carries `hide-theme-dark`) |

Both settings are managed through the application settings store; an empty/unset setting results in an `<img>` with an empty `src`.

## Usage

Basic usage with defaults (lazy loading, theme-aware):

```blade
<x-logo />
```

Real usage in the dashboard sidebar brand (`resources/layouts/dashboard/partials/sidebar/base.blade.php`):

```blade
<div class="navbar-brand-image d-flex align-items-center"><x-logo /></div>
```

In the page loader, where the logo must appear immediately so lazy loading is disabled and a margin class is added (`resources/components/page-loader.blade.php`):

```blade
<div><x-logo class="mb-4" :lazy="false" /></div>
```

The extra `class="mb-4"` is merged onto both `<img>` tags alongside their respective `hide-theme-*` classes.

It is also used directly in the auth layout (`resources/layouts/dashboard/auth.blade.php`) and the website navbar (`resources/layouts/website/partials/navbar.blade.php`):

```blade
<x-logo />
```

## Gotchas

- The component always renders **two** `<img>` elements, not one. CSS (the `hide-theme-*` classes) controls which is visible; both are present in the DOM.
- `lazy` only affects the `loading` attribute. Use `:lazy="false"` for above-the-fold or splash/loader contexts where you don't want deferred loading.
- Classes you pass via `class="..."` apply to both images, so they affect the dark and light logo equally.
- Both logos depend on the `app_logo_dark` and `app_logo_light` settings being configured; otherwise their `src` is empty.

## Related

- [Page loader component](/components/page-loader) — uses `<x-logo :lazy="false" />`.
