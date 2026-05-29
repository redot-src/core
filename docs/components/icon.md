# Icon

The `<x-icon>` component renders an icon, either by emitting an icon-font `<i>` element (Font Awesome style classes) or by inlining a raw SVG/HTML markup string. It is an anonymous Blade component with no backing PHP class.

## What it is

`resources/components/icon.blade.php` is a small, dependency-free anonymous component. It branches on the value of the `icon` prop:

- If `icon` **starts with `<`**, the value is treated as raw markup (e.g. an inline `<svg>...</svg>`) and echoed unescaped.
- Otherwise, `icon` is treated as a CSS class string and rendered as `<i aria-hidden="true" class="...">`.

There is no associated `app/View/Components/Icon.php` class — all behavior lives in the Blade view.

## Props

| Prop | Type | Required | Default | Description |
| --- | --- | --- | --- | --- |
| `icon` | string | Yes | — | Either an icon CSS class string (e.g. `fas fa-plus`) or a raw markup string beginning with `<` (e.g. an inline SVG). |

The component declares only `@props(['icon'])`. `icon` has no default, so it must always be supplied.

### Attributes

Any extra attributes are forwarded via Laravel's attribute bag. In the icon-font branch they are merged onto the `<i>` element through <span v-pre>`{{ $attributes->class([$icon]) }}`</span>, meaning:

- The value of `icon` is appended to the element's class list.
- Any `class="..."` you pass on the tag is merged with it.
- Other attributes are **not** rendered in the `<i>` branch (only `class` is applied, plus the hard-coded `aria-hidden="true"`).

In the raw-markup branch, attributes are ignored — the string is printed as-is.

### Source

```blade
@props(['icon'])

@if (str_starts_with($icon, '<'))
    {!! $icon !!}
@else
    <i aria-hidden="true" {{ $attributes->class([$icon]) }}></i>
@endif
```

## Slots

The component takes no slot; content is driven entirely by the `icon` prop.

## Livewire / wire:model

The component has no `wire:model` or Livewire-specific behavior.

## Usage

Pass a Font Awesome class string directly:

```blade
<x-icon icon="fas fa-plus" />
<x-icon icon="fas fa-times" />
```

Pass a dynamic class and merge extra classes onto the rendered `<i>`:

```blade
<x-icon :icon="$icon" class="fa-3x" />
```

This renders:

```html
<i aria-hidden="true" class="fa-3x your-icon-classes"></i>
```

## Examples

Real usages from the dashboard:

Sidebar menu item, binding a model's icon class (`resources/layouts/dashboard/partials/sidebar/item.blade.php`):

```blade
<x-icon :icon="$item->icon" />
```

Empty-state illustration with a sizing modifier merged in (`resources/components/empty.blade.php`):

```blade
<x-icon :icon="$icon" class="fa-3x" />
```

Alert leading icon (`resources/components/alert.blade.php`):

```blade
<x-icon :icon="$icon" class="alert-icon icon" />
```

Date picker trigger (`resources/components/date-picker.blade.php`):

```blade
<x-icon icon="fa fa-calendar-alt" />
```

## Gotchas

- `icon` is required — there is no fallback. Omitting it throws an undefined-variable error.
- The raw-markup branch is detected purely by a leading `<`. Leading whitespace before the `<` will cause it to be treated as a CSS class instead and rendered inside `class="..."`.
- In the SVG/raw-markup branch, any `class` or other attributes you put on the tag are dropped; style the markup itself instead.
- The icon-font branch hard-codes `aria-hidden="true"`; use an adjacent label or `aria-label` on a wrapping element for accessible labeling.

## Related

- [Icon Picker component](/components/icon-picker) — UI for selecting an icon class to feed into `<x-icon>`.
