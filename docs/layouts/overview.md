# Layouts

Redot Core ships a small family of page layouts that every view wraps
itself in. You pick one by writing its tag at the top of a view — they are all
exposed under the `layouts` component namespace.

## Usage

Wrap a page's content in the layout you want:

```blade
<x-layouts::dashboard>
    {{-- page content --}}
</x-layouts::dashboard>
```

The tag name maps to the layout: `<x-layouts::dashboard>` selects the dashboard
layout, and a nested one like `<x-layouts::dashboard.auth>` selects the dashboard
auth layout. Full-page Livewire components render inside the same base layout
automatically, so they look identical to regular pages.

## Available layouts

- **`<x-layouts::scaffold>`** — the base HTML document every other layout builds
  on (CSRF meta, stylesheets, scripts, RTL/LTR handling). You rarely use it
  directly.
- **`<x-layouts::dashboard>`** — admin / back-office pages, with the sidebar,
  navbar, and footer.
- **`<x-layouts::dashboard.auth>`** — dashboard auth screens (login, unlock,
  forgot password): centered, logo, no chrome.
- **`<x-layouts::website>`** — public site pages, with the website navbar and
  footer.
- **`<x-layouts::website.auth>`** — website auth screens (login, register,
  password reset): centered content.
- **`<x-layouts::pdf>`** — server-rendered PDF templates (datatable exports,
  documents).
- **`layouts.error`** — error pages (404, 500, …); used with `@extends`.

## Options

The dashboard and website layouts accept:

- **`title`** — the page title (appended to the app name in the document title).
  The dashboard layout defaults this to the active sidebar item's title.
- **`inline`** — hide the sidebar, navbar, and footer to embed a page without
  chrome (handy for modals or iframes). Appending `?inline` to the request URL
  forces inline mode too.

The scaffold and PDF layouts also accept:

- **`title`** — the page title.
- **`direction`** — `ltr` or `rtl`. Defaults to the current language's
  direction, which selects the matching stylesheet.

## Examples

### Dashboard page

```blade
<x-layouts::dashboard :title="__('Posts')">
    {{-- index.blade.php --}}
</x-layouts::dashboard>
```

### Chrome-less embed

```blade
<x-layouts::dashboard inline>
    <div class="card">
        {{-- shown inside a modal --}}
    </div>
</x-layouts::dashboard>
```

### Dashboard auth screen

```blade
<x-layouts::dashboard.auth :title="__('Login to your account')">
    <x-form class="card card-md" :action="route('login.store')" method="POST">
        {{-- ... --}}
    </x-form>
</x-layouts::dashboard.auth>
```

### Website page

```blade
<x-layouts::website :title="$post->title">
    {{-- posts/show.blade.php --}}
</x-layouts::website>
```

### PDF template

```blade
<x-layouts::pdf>
    <table>
        {{-- export rows --}}
    </table>
</x-layouts::pdf>
```

### Error page

```blade
@extends('layouts.error', ['error' => '404'])

@section('title', __('Page not found'))
```

The error layout reads an `error` value (the big header, default `500`), a
`title` section (the message line), an optional `subtitle` section, and an
optional `action` section (falling back to a "Back to Home" button).

## The sidebar

The dashboard layout builds its sidebar from `app/sidebar.php` on every render.
Edit that file — not the layout partials — to change the menu. See
[Sidebar](/packages/sidebar).

## Related

- [Templates](/layouts/templates) — reusable partials for PDF export and remote selects.
- [Sidebar](/packages/sidebar) — defining the dashboard menu.
- [Settings](/foundation/settings) — the theme, logos, and toggles the layouts read.
- [Asset & Init System](/frontend/asset-system) — the styles and scripts layouts load.
