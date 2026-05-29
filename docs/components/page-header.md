# Page Header

`<x-page-header>` renders the standard heading block at the top of dashboard pages: a small pretitle line, the page title, and a right-aligned action area that can include an automatic "Create" button plus any custom action buttons passed as slot content.

## What it is

An **anonymous Blade component** (no PHP class) defined at `resources/components/page-header.blade.php`. It produces a Tabler-style `.page-header` block with a `.row` containing the title column and an action column (`.btn-list`).

## Props and attributes

The component uses `@props` for its inputs and `@aware` to inherit the page title from the surrounding layout.

| Prop | Default | Description |
| --- | --- | --- |
| `title` | inherited via `@aware`, falling back to `__('Dashboard')` | The main `<h2 class="page-title">` text. |
| `pretitle` | `__('Overview')` | The small `.page-pretitle` line shown above the title. |
| `create` | `null` | A URL. When set (and permitted), renders a primary "Create" button linking to it. |

```php
@aware([
    'title' => __('Dashboard'),
])

@props([
    'title' => null,
    'pretitle' => __('Overview'),
    'create' => null,
])
```

### Title resolution (`@aware`)

`title` is declared with `@aware`, so if it is not passed explicitly it is pulled from a `title` attribute set on an ancestor component. In the dashboard this flows from the layout chain (`resources/layouts/dashboard/base.blade.php` forwards `:title` into `<x-layouts::scaffold>`). If neither an explicit `:title` nor an aware ancestor value is present, it falls back to `__('Dashboard')`.

### The "Create" button and permission gating

When `create` is provided, the button is rendered **only if** `url_allowed($create)` returns true:

```blade
@if ($create && url_allowed($create))
    <a href="{{ $create }}" class="btn btn-primary">
        <i class="fa fa-plus me-2"></i>
        {{ __('Create') }}
    </a>
@endif
```

`url_allowed()` is a `redot/core` helper (`url_allowed(string $url, string $guard = 'admins'): bool`). External URLs always pass; internal URLs are checked against the resolved route's authorization for the `admins` guard. This means the Create button is automatically hidden from users who lack permission to reach the create route. See [Helpers](/foundation/helpers) and [Middleware](/foundation/middleware).

### Attributes passthrough

The root `<div>` merges incoming attributes and always includes the classes `page-header d-print-none mt-0`:

```blade
<div {{ $attributes->class(['page-header d-print-none mt-0']) }}>
```

So passing `class="mb-3"` adds to (does not replace) the base classes.

## Slots

The component exposes the **default slot**, rendered inside the right-aligned `.btn-list`, after the optional Create button. Use it for extra action buttons, dropdowns, or filters:

```blade
<div class="col-auto ms-auto d-print-none">
    <div class="btn-list">
        @if ($create && url_allowed($create))
            {{-- Create button --}}
        @endif

        {{ $slot }}
    </div>
</div>
```

There are no named slots.

## Examples

Self-closing with just an auto Create button (from `resources/views/dashboard/users/index.blade.php`, same pattern in admins, roles, languages, memos, shortened-urls, static-pages):

```blade
<x-page-header :create="route('dashboard.users.create')" class="mb-3" />
```

Explicit title and pretitle plus custom action content in the slot (from `resources/views/dashboard/languages/tokens/index.blade.php`):

```blade
<x-page-header :title="__('Language Tokens')" :pretitle="$language->name" class="mb-3">
    <div class="dropdown">
        <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-cog me-2"></i>
            {{ __('Actions') }}
        </a>

        <div class="dropdown-menu">
            <a class="dropdown-item" action-confirm
               href="{{ route('dashboard.languages.tokens.publish', ['language' => $language]) }}">
                <span class="dropdown-item-icon"><i class="fas fa-upload me-2"></i></span>
                <span class="dropdown-item-title">{{ __('Publish Tokens') }}</span>
            </a>
            {{-- ...more items... --}}
        </div>
    </div>
</x-page-header>
```

## Gotchas

- No PHP class backs this component; all logic is in the Blade view.
- The Create button text and icon are fixed (`fa fa-plus` + translated `Create`); for any other action, use the slot instead of `create`.
- `pretitle` defaults to the translated string `Overview` — pass `:pretitle` to override it (e.g. the tokens page uses the language name).
- The Create button silently disappears when `url_allowed()` denies the URL; an empty action area is expected behavior, not a bug.
- Because `title` is `@aware`, setting it on the layout once removes the need to repeat `:title` on every page header.
