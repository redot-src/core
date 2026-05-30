# Stylesheets

The dashboard ships its CSS for you — Tabler, Font Awesome, the theme tokens, and
the component styles are all loaded by the layout. You only touch this page when
you want to add your own styles on top, and the main thing to get right is *which
stack to push to* so your CSS lands in the right place in the cascade.

## Adding page-specific CSS

Push a `<link>` to the `styles` stack. It renders after all the global styling,
so your rules win:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ hashed_asset('/assets/css/my-feature.css') }}" />
@endpush
```

## Adding a plugin's CSS

For a third-party widget's stylesheet, push to `plugins-styles` instead. It
renders earlier — alongside the vendor bundles, before the app's own CSS — which
is what plugin stylesheets expect:

```blade
@push('plugins-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/my-plugin/plugin.css') }}" />
@endpush
```

Always reference files through [`hashed_asset()`](/foundation/helpers) so the URL
is cache-busted when the file changes.

## Staying on-theme

Write your CSS against Tabler's custom properties (`--tblr-primary`,
`--tblr-body-bg`, `--tblr-gray-500`, …) rather than hard-coded colors. Doing so
makes your styles follow the active theme and dark mode automatically — see
[Theming](/frontend/theming). Dark mode is keyed off `[data-bs-theme='dark']` on
`<html>` if you need an explicit override.

## Gotchas

- **The app font stack is forced** with `!important`; overriding
  `--tblr-font-sans-serif` elsewhere won't take effect — change the font via the
  [theme](/frontend/theming) `font` axis instead.
- **PDF rendering is separate.** Server-side PDFs use their own standalone
  stylesheet with no theme context — plain colors only, no `--tblr-*` variables.

## Related

- [Theming](/frontend/theming) — the theme tokens your CSS should consume.
- [Asset & Init System](/frontend/asset-system) — `hashed_asset()` and how assets load.
- [Helpers](/foundation/helpers) — `hashed_asset()`.
