# Page Header

`<x-page-header>` renders the standard heading block at the top of a dashboard
page: a small pretitle, the page title, and a right-aligned action area with an
optional "Create" button and any extra buttons you add.

## Usage

```blade
<x-page-header :create="route('dashboard.users.create')" class="mb-3" />
```

## Options

- **`title`** — the main heading. Inherited from the surrounding layout when not
  set, so you usually don't need to pass it.
- **`pretitle`** — the small line above the title (defaults to "Overview").
- **`create`** — a URL for the primary "Create" button. The button only appears
  if the current user is allowed to reach that URL, so it's automatically hidden
  from users without permission.

Any extra classes are merged onto the header.

## Examples

### Custom title, pretitle, and action buttons

Put extra actions (dropdowns, filters, buttons) in the slot — they render in the
right-aligned action area:

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
        </div>
    </div>
</x-page-header>
```

## Related

- [Layouts](/layouts/overview) — the page shells page headers live inside.
- [Components overview](/components/overview) — attribute pass-through.
