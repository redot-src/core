# Stylesheets

The Redot Dashboard ships a small set of hand-written CSS files under `public/assets/css/`. They layer custom styling and vendor overrides on top of the [Tabler](https://tabler.io/) UI kit and [Font Awesome 6](https://fontawesome.com/), which are loaded as vendor bundles. This page catalogs each stylesheet, what it does, and where it is loaded.

## The vendor stack

Every dashboard/website page is rendered through `resources/layouts/scaffold.blade.php`, which loads the vendor CSS first, then the app's own stylesheets. The base layer is:

```blade
<link rel="stylesheet" href="{{ hashed_asset("/vendor/tabler/css/tabler.$direction.min.css") }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/fontawesome/css/all.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/jquery-confirm/jquery-confirm.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/vendor/fancybox/fancybox.min.css') }}" />

@toastifyCss
@stack('plugins-styles')

<link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler/css/tabler-vendors.min.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset('/assets/css/app.css') }}" />
<link rel="stylesheet" href="{{ hashed_asset("/assets/css/themer.css") }}" />
<link rel="stylesheet" href="{{ hashed_asset('/assets/css/overrides.css') }}" />

@stack('styles')
```

Notes:

- Tabler is loaded direction-aware (`tabler.ltr.min.css` / `tabler.rtl.min.css`) based on the page `$direction`.
- All of the app's CSS is referenced through the `hashed_asset()` helper, which appends a cache-busting hash to the URL.
- Custom styles consistently consume Tabler's CSS custom properties (`--tblr-*`) rather than hard-coded colors, so they automatically follow the active theme (including dark mode via `[data-bs-theme='dark']`).
- Font Awesome glyphs are injected through CSS `content` using `var(--fa-font-solid)` and `font-family: 'Font Awesome 6 Free'`.

## Load order summary

| File | Loaded in | Mechanism |
| --- | --- | --- |
| `app.css` | `scaffold.blade.php` (all dashboard/website pages) | direct `<link>` |
| `overrides.css` | `scaffold.blade.php` (after `app.css` + `themer.css`) | direct `<link>` |
| `dashboard.css` | `layouts/dashboard/base.blade.php` | `@pushOnce('styles')` |
| `website.css` | `layouts/website/base.blade.php` | `@pushOnce('styles')` |
| `pdf.css` | `layouts/pdf.blade.php` | direct `<link>` via `public_path()` |

`app.css`, `themer.css`, and `overrides.css` are the global trio for every browser page. `dashboard.css` and `website.css` are pushed in by the respective layout. `pdf.css` is completely separate and used only for server-side PDF rendering.

## `app.css` — fonts + core component styles

The global stylesheet. It does three things:

1. **Registers the app fonts.** `@font-face` declarations for the full `IBM Plex Sans Arabic` weight family (100–700) and the variable `DM Sans` font, all served from `../fonts/`. It then wires them into Tabler:

   ```css
   :root,
   :host {
       --tblr-font-sans-serif: 'DM Sans', 'IBM Plex Sans Arabic', sans-serif !important;
       font-feature-settings: 'cv03', 'cv04', 'cv11';
   }
   ```

2. **Global utilities.** A handful of cross-cutting helpers:
   - `.hidden-on-empty:empty { display: none; }`
   - `[visible-when]:not([is-visible='true'])` — the styling half of the Redot visibility directive (an element with `visible-when` stays hidden until JS sets `is-visible="true"`).
   - `[role='tab'].has-invalid-feedback` — turns tab labels red when they contain validation errors.

3. **Styling for the Redot Blade components.** The bulk of the file. Each block is scoped by the component's marker attribute or class:
   - `.page-loader` — the full-screen [Page Loader](/components/page-loader) overlay.
   - `[iconpicker-modal]` / `[iconpicker-list]` / `[iconpicker-icon]` — the [Icon Picker](/components/icon-picker) grid.
   - `[translatable-tab]` / `[translatable-switcher]` — the [Translatable](/components/translatable) tabs, including the FA cross glyph injected on invalid tabs.
   - `.rating-field` — the reverse-row star [Rating](/components/rating) field (uses `--star-size` and `--tblr-yellow`).
   - `.repeater-toolbar` / `[repeater-item]` — the [Repeater](/components/repeater) component.
   - `[uploader-container]` / `[uploader-wrapper]` / `.uploader-item*` / `[attachments-list]` — the [Uploader](/components/uploader) component, including drag-over, per-item status states (`status-pending`, `status-uploading`, `status-error`, `status-uploaded`), Fancybox preview overlay, progress bar, and SortableJS ghost/drag styles.

   This is where most component-specific selectors live; the component pages above are the canonical reference for the markup that these rules target.

## `overrides.css` — vendor tweaks

Loaded right after `app.css` and `themer.css`. It contains no original components — it is purely a stack of fixes and theme-integration overrides for Tabler/Bootstrap and the bundled JS vendor libraries, each section labeled with a banner comment:

- **Tabler & Bootstrap** — pagination spacing, input-group radius/focus fixes, dark-theme focus borders, number-input spinner removal, `.has-invalid-feedback` validation styling, RTL placeholder alignment, theme-light/dark visibility helpers (`.hide-theme-light` / `.hide-theme-dark`), `.nav.text-on-active` behavior, card-footer padding, navbar dropdown active state.
- **Toastify** — layout and shadow for `.toastify` notification toasts.
- **TinyMCE** — transparent edit-area iframe and invalid-state border.
- **Tom Select** — single-select wrapping, `plugin-remove_button` styling (with an FA cross via `content: '\f00d'`), invalid-state border, dropdown hover.
- **jQuery Confirm** — restyles `.jconfirm-material` dialogs to use Tabler surface colors and a backdrop blur.
- **Coloris** — color-picker field button placement and segmented control colors.
- **Tempus Dominus** — maps the date/time picker's `--td-*` variables (both light and dark) onto `--tblr-*` values.
- **jQuery QueryBuilder** — full restyle of the query-builder rules/groups to match Tabler (used by the filter builder).
- **Redot Datatable** — `.datatable .tags-list` nowrap.
- **Fancybox** — lightbox toolbar/close-button/thumbnail/progress styling and per-content-type padding.

These selectors target third-party DOM, so they are intentionally heavy on `!important` and `var(--tblr-*)` to keep vendor widgets on-theme.

## `dashboard.css` — dashboard chrome layout

Pushed into the `styles` stack by `layouts/dashboard/base.blade.php`. It styles the dashboard shell only and is scoped under `.dashboard-page` / the `standard-layout` class:

- `--dashboard-sidebar-margin: 10px` — the gutter variable that drives the floating sidebar/navbar spacing.
- `.dashboard-search` — the navbar search box and its dropdown menu.
- `.dashboard-sidebar` — floating bordered, rounded sidebar.
- `.dashboard-navbar` — sticky, blurred (`backdrop-filter`) translucent top navbar with `z-index: 9999`.
- `.standard-layout .page-wrapper` — content offset by the sidebar width (`--tblr-sidebar-width`).
- Responsive blocks at `min-width: 992px` (desktop floating layout, including a `::before` mask strip behind the sticky navbar) and `max-width: 991.98px` (mobile: square corners, icon-only navbar links).

This file pairs with the dashboard layout partials (`sidebar`, `navbar`, `footer`).

## `website.css` — public site tweaks

Pushed in by `layouts/website/base.blade.php`. It is tiny — a single rule that removes the hover background and applies the primary color on footer nav links:

```css
footer .nav .nav-item .nav-link:hover {
    background-color: unset;
    color: var(--tblr-primary);
}
```

## `pdf.css` — print/PDF stylesheet

Completely standalone. Loaded by `layouts/pdf.blade.php` via `public_path()` (a filesystem path, not a hashed URL) so the PDF renderer can read it from disk. It does **not** depend on Tabler or any `--tblr-*` variable — it uses plain hex colors and a float-based grid, suitable for an mPDF-style HTML-to-PDF engine:

- `@page` rule wiring `page-header` / `page-footer` named elements and a bottom margin (mPDF page directives).
- Base typography for `body`, headings, `p`, `a`, `table` (`th`/`td` borders), and lists.
- `.page-break { page-break-before: always; }`.
- A float-based 12-column grid: `.row`, `.col-left`/`.col-right`/`.clearfix`, and `.col-1` … `.col-12` width classes.
- Font-weight utilities: `.font-bold`, `.font-semibold`, `.font-medium`, `.font-normal`.

The matching layout supplies the named footer element it references:

```blade
<htmlpagefooter name="page-footer">
    <div class="pb-4 text-center">
        {{ __('Page {PAGENO} of {nb}') }}
    </div>
</htmlpagefooter>
```

## Usage

You normally never reference these files directly — they are loaded by the layouts. To add page-specific CSS on top of the global stack, push to the `styles` stack (it renders after `app.css`/`themer.css`/`overrides.css`):

```blade
@push('styles')
    <link rel="stylesheet" href="{{ hashed_asset('/assets/css/my-feature.css') }}" />
@endpush
```

For a third-party plugin's CSS, push to `plugins-styles` instead, which renders earlier (alongside the vendor bundles, before `app.css`):

```blade
@push('plugins-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/my-plugin/plugin.css') }}" />
@endpush
```

## Gotchas

- **Use `--tblr-*` variables, not hard-coded colors,** in any new browser-facing CSS so it stays theme- and dark-mode-aware. `pdf.css` is the exception — it has no theme context.
- **`overrides.css` loads last** of the global trio, so it intentionally wins over Tabler and `app.css`; that is why many of its rules use `!important`.
- **`pdf.css` is loaded from disk** (`public_path()`), not over HTTP, and is unhashed — there is no cache-busting and no vendor CSS available inside the PDF.
- The font feature settings and font stack are forced with `!important` in `app.css`; overriding `--tblr-font-sans-serif` elsewhere will not take effect.

## Related pages

- [Uploader component](/components/uploader)
- [Icon Picker component](/components/icon-picker)
- [Translatable component](/components/translatable)
- [Rating component](/components/rating)
- [Repeater component](/components/repeater)
- [Page Loader component](/components/page-loader)
