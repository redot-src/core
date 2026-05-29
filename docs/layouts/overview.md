# Layouts

The Redot Dashboard ships a small family of page layouts that every view wraps itself in. They come in two flavours: **class-based layouts** (`App\View\Layouts\*`) that contain logic, and **anonymous Blade layouts** (`resources/layouts/*.blade.php`) that are pure markup. All of them are exposed under a single `layouts` component namespace, so you select one by writing `<x-layouts::name>` at the top of a view.

## How layouts are registered

The core package wires up both styles under the `layouts` prefix in `RedotServiceProvider`:

```php
Blade::anonymousComponentPath(resource_path('layouts'), 'layouts');
Blade::componentNamespace('App\\View\\Layouts', 'layouts');
```

This means:

- A class like `App\View\Layouts\Dashboard` is reachable as `<x-layouts::dashboard>`.
- A Blade file like `resources/layouts/scaffold.blade.php` is reachable as `<x-layouts::scaffold>`.
- A nested Blade file like `resources/layouts/dashboard/auth.blade.php` is reachable as `<x-layouts::dashboard.auth>`.

To pick a layout, a page wraps its content in the chosen tag:

```blade
<x-layouts::dashboard>
    {{-- page content --}}
</x-layouts::dashboard>
```

## The layout stack

There is one root document layout (`scaffold`) that every other layout renders into. The class-based layouts (`Dashboard`, `Website`, `Pdf`, `Scaffold`) add behavior, then delegate to a Blade view of the same name.

| Tag | Backing class / file | Use it for |
| --- | --- | --- |
| `<x-layouts::scaffold>` | `App\View\Layouts\Scaffold` → `layouts.scaffold` | The base HTML document. Other layouts build on it; you rarely use it directly. |
| `<x-layouts::dashboard>` | `App\View\Layouts\Dashboard` → `layouts.dashboard.base` | Admin/back-office pages (sidebar + navbar + footer). |
| `<x-layouts::dashboard.auth>` | `layouts/dashboard/auth.blade.php` (anonymous) | Dashboard auth screens (login, unlock, forgot password). |
| `<x-layouts::website>` | `App\View\Layouts\Website` → `layouts.website.base` | Public site pages (website navbar + footer). |
| `<x-layouts::website.auth>` | `layouts/website/auth.blade.php` (anonymous) | Website auth screens (login, register, reset). |
| `<x-layouts::pdf>` | `App\View\Layouts\Pdf` → `layouts.pdf` | Server-rendered PDF templates. |
| `layouts.error` | `layouts/error.blade.php` (anonymous) | Error pages (404/500/…). |

The Livewire config (`config/livewire.php`) points its `component_layout` at `layouts::scaffold` and registers `resources/layouts` as a view path, so full-page Livewire components render inside the same scaffold.

## `<x-layouts::scaffold>`

The root document. `App\View\Layouts\Scaffold` exposes two constructor props:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `title` | `?string` | `null` | Suffixed with `app_name()`; if empty, just `app_name()`. |
| `direction` | `?string` | `null` | Falls back to `Redot\Models\Language::current()->direction` (`ltr`/`rtl`). |

The Blade `layouts.scaffold` builds the `<html>`/`<head>`/`<body>` shell: CSRF meta, favicon, the RTL/LTR Tabler stylesheet (`tabler.$direction.min.css`), FontAwesome, jQuery-Confirm, Fancybox, the app/themer/overrides CSS, then the <span v-pre>`{{ $slot }}`</span>. At the bottom it loads jQuery, lodash, Tabler, `functions.js`, the validator/visibility plugins, the locale translation script, `init.js`, and `app.js`, and hydrates `window.OldBag` / `window.ErrorsBag` from old input and the error bag (auto-appending validation errors to the matching form).

It exposes these stacks for child layouts and pages to push into: `meta`, `plugins-styles`, `styles`, `pre-content`, `templates`, `plugins-scripts`, `scripts`.

## `<x-layouts::dashboard>`

The main back-office layout. `App\View\Layouts\Dashboard` props:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `title` | `?string` | `null` | If omitted, defaults to the active sidebar item's title. |
| `inline` | `bool` | `false` | When `true` (or `?inline` is present on the request), the sidebar, navbar and footer are hidden — used for embedding a page inside a modal/iframe. |

On render it resolves the current admin via `current_admin()`, loads the sidebar by `include`-ing `app/sidebar.php` (a `Redot\Sidebar\Sidebar` instance), and pulls `$items` and the `$active` item from it. See [the Sidebar package](/packages/sidebar) for how items are defined.

`layouts.dashboard.base` applies the `dashboard-theme`, pushes `assets/css/dashboard.css` / `assets/js/dashboard.js`, optionally renders `<x-page-loader />` (when the `page_loader_enabled` setting is on), and lays out the page as `<main class="page dashboard-page">`. Unless `inline`, it includes the sidebar, navbar, and footer partials.

```blade
<x-layouts::dashboard>
    {{-- index.blade.php --}}
</x-layouts::dashboard>
```

```blade
<x-layouts::dashboard inline>
    <div class="card">
        {{-- memos/show.blade.php — chrome-less embed --}}
    </div>
</x-layouts::dashboard>
```

### Sidebar partials

The sidebar is built from `$items` (the `Redot\Sidebar\Item` objects) by `layouts/dashboard/partials/sidebar/base.blade.php`. It renders the brand logo, a collapse toggle, then loops the items: an item with `children` becomes a dropdown, otherwise a plain link. A "Logout" link that submits the hidden logout form is appended last. The sidebar's theme comes from the `dashboard_sidebar_theme` setting (`data-bs-theme`).

The partials read these properties off each `Item`:

- `item/item.blade.php` — `$item->active`, `$item->url`, `$item->external` (opens in a new tab), `$item->icon` (rendered with `<x-icon>`), `$item->title`.
- `dropdown.blade.php` — `$item->active`, `$item->icon`, `$item->title`, `$item->children` (loops `dropdown-item`). The menu is shown expanded when the item is active.
- `dropdown-item.blade.php` — `$item->active`, `$item->url`, `$item->icon`, `$item->title`.

These mirror the `Item` API from [the Sidebar package](/packages/sidebar) — see `app/sidebar.php` for how items are declared (`Item::make()->title(...)->icon(...)->route(...)` / `->children([...])`).

### Navbar and footer

`layouts/dashboard/partials/navbar.blade.php` renders the top bar: a page search box (`#search-pages`), an optional "Preview" link to the website (when `redot.features.website.enabled`), a locale switcher (shown when more than one `dashboard_locales` setting), a dark/light theme toggle (`data-theme="dark"`/`"light"`), and an admin dropdown (`<x-avatar>`, profile link, a "Lock" form posting to `dashboard.lock`, and logout). It also defines the hidden `#logout-form` that posts to `dashboard.logout`.

`layouts/dashboard/partials/footer.blade.php` is a static copyright/credit line built from `app_name()` and `config('app.url')`.

## `<x-layouts::dashboard.auth>`

An anonymous layout for dashboard auth screens. It nests inside `scaffold`, applies `dashboard-theme`, centers the content with the logo (`<x-logo>`) linking to `dashboard.index`, renders `<x-status />` and the <span v-pre>`{{ $slot }}`</span>, and shows a locale switcher when more than one `dashboard_locales` is configured. Any attributes you pass are merged onto the scaffold (it defaults to `d-flex flex-column`).

```blade
<x-layouts::dashboard.auth :title="__('Login to your account')">
    <x-form class="card card-md" :action="route('dashboard.login.store')" method="POST">
        {{-- ... --}}
    </x-form>
</x-layouts::dashboard.auth>
```

## `<x-layouts::website>`

The public site layout. `App\View\Layouts\Website` props:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `title` | `?string` | `null` | Passed through to the scaffold. |
| `inline` | `bool` | `false` | `true` (or `?inline` on the request) hides the navbar and footer. |

`layouts.website.base` applies `website-theme`, pushes `assets/css/website.css` plus the raw `head_code` setting, renders the website navbar/footer (unless inline), includes the Google Analytics and Facebook Pixel partials, and pushes `assets/js/website.js` plus the raw `body_code` setting.

```blade
<x-layouts::website :title="$staticPage->title">
    {{-- static-pages/show.blade.php --}}
</x-layouts::website>
```

The website navbar (`partials/navbar.blade.php`) has the brand, a theme toggle, a locale switcher driven by the `website_locales` setting, and an account dropdown that branches on `auth('users')` (profile/logout vs. login/register). The footer (`partials/footer.blade.php`) lists `StaticPage` quick links, contact info, and a newsletter `<x-form>`.

## `<x-layouts::website.auth>`

A thin anonymous wrapper around `website` that centers content in `container container-tight`. Used by website login/register/password screens.

```blade
<x-layouts::website.auth :title="__('Login')">
    {{-- ... --}}
</x-layouts::website.auth>
```

## `<x-layouts::pdf>`

A minimal HTML document tuned for PDF rendering (e.g. mPDF). `App\View\Layouts\Pdf` props:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `title` | `?string` | `null` | Suffixed with `app_name()`. |
| `direction` | `?string` | `null` | Falls back to `Language::current()->direction`. |

`layouts.pdf` loads `assets/css/pdf.css` via `public_path()` (not a web URL, since the renderer reads from disk), shows the light app logo (`app_logo_light` setting), renders the <span v-pre>`{{ $slot }}`</span>, and adds a `<htmlpagefooter>` with a "Page {PAGENO} of {nb}" footer.

```blade
<x-layouts::pdf>
    <table>
        {{-- datatable export rows --}}
    </table>
</x-layouts::pdf>
```

## `layouts.error`

An anonymous layout for error pages, built on `scaffold` with the `website-theme`. It renders Tabler's `empty` state and exposes:

- An `error` prop (default `'500'`) shown as the big header.
- `@yield('title')` — the message line.
- `@section('subtitle')` — optional muted subtitle.
- `@section('action')` — optional action area; falls back to a "Back to Home" button when not provided.

```blade
@extends('layouts.error', ['error' => '404'])

@section('title', __('Page not found'))
```

## Gotchas

- **Two registrations, one prefix.** Both the `App\View\Layouts` classes and the `resources/layouts` Blade files live under `layouts::`. Class layouts add logic (title resolution, sidebar loading, direction); the bare Blade files are markup-only.
- **`inline` is request-aware.** `?inline` on the URL forces inline mode on `dashboard`/`website` even if the attribute is not set — handy for embedding a page without chrome.
- **Direction drives the stylesheet.** `scaffold` loads `tabler.$direction.min.css`, so `direction` controls RTL/LTR. It defaults to the current language's direction.
- **Sidebar comes from `app/sidebar.php`.** The dashboard layout loads it on every render. Edit that file (not the partials) to change the menu — see [the Sidebar package](/packages/sidebar).
