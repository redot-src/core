# Flag

`<x-flag>` renders a country flag as a CSS-styled `<span>`, using the Tabler Flags sprite. It is a lightweight, self-contained anonymous Blade component that ships its own stylesheet on first use.

## What it is

The component lives at `resources/components/flag.blade.php` and is an **anonymous Blade component** — there is no backing `app/View/Components/Flag.php` class. Its public API is defined entirely by the `@props` directive.

When rendered, it outputs a single `<span>` whose CSS classes drive the flag image:

```blade
<span class="flag flag-md flag-country-eg"></span>
```

The flag artwork comes from the Tabler Flags CSS (`vendor/tabler/css/tabler-flags.min.css`), which is pushed once into the `plugins-styles` stack the first time any `<x-flag>` (or `<x-countries>`) appears on the page.

## Props

| Prop | Type | Default | Description |
| ---- | ---- | ------- | ----------- |
| `code` | string | `'eg'` | ISO country code used to build the `flag-country-{code}` class. |
| `size` | string | `'md'` | Size token used to build the `flag-{size}` class (e.g. `xs`, `sm`, `md`, `lg` as supported by Tabler Flags). |

Any additional HTML attributes are merged onto the `<span>` via Laravel's `$attributes` bag. Extra classes you pass are merged with the component's built-in `flag`, `flag-{size}`, and `flag-country-{code}` classes (see `$attributes->class([...])`).

There are no slots — the component renders an empty, CSS-painted `<span>`.

## Behavior

- **Stylesheet injection.** The component uses `@pushOnce('plugins-styles', 'tabler-flags-styles')` to load `tabler-flags.min.css` exactly once per request, regardless of how many flags are rendered. The asset URL is resolved through the `hashed_asset()` helper for cache-busting.
- **No JavaScript.** This is a pure CSS/markup component; it binds no JS plugin and has no `wire:model` or other Livewire behavior.

## Source

```blade
@props([
    'code' => 'eg',
    'size' => 'md',
])

@pushOnce('plugins-styles', 'tabler-flags-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler/css/tabler-flags.min.css') }}" />
@endPushOnce

<span {{ $attributes->class(['flag', "flag-$size", "flag-country-$code"]) }}></span>
```

## Usage

Render a flag with the defaults (Egypt, medium size):

```blade
<x-flag />
```

Specify a country code and size:

```blade
<x-flag code="us" size="lg" />
```

Merge extra attributes (classes, title, etc.) onto the span:

```blade
<x-flag code="fr" size="sm" class="me-2" title="France" />
```

## Related

The same underlying Tabler Flags classes (`flag`, `flag-{size}`, `flag-country-{code}`) are used directly by the country select template. For example, `resources/templates/select/country.blade.php` renders:

```blade
<span class="flag flag-xs flag-country-{{ $item->code }}"></span>
```

That template is consumed by the [Countries component](/components/countries), which wires a [`<x-select>`](/components/select) to `App\Models\Country` and pushes the same `tabler-flags-styles` stylesheet:

```blade
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

If you need a selectable, flag-decorated country dropdown rather than a standalone flag glyph, prefer [`<x-countries>`](/components/countries) over `<x-flag>`.
