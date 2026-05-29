# Empty

`<x-empty>` is an anonymous Blade component that renders a Tabler-styled empty state inside a card — an icon, a title, and a subtitle — for use when a list, page, or section has nothing to display.

## What it is

A pure-Blade anonymous component (there is no backing PHP class in `app/View/Components`). It is defined entirely in `resources/components/empty.blade.php` and exposes its props via `@props`. It wraps an `<x-icon>` for the icon and uses Tabler's `empty` / `empty-icon` / `empty-title` / `empty-subtitle` markup.

## Props

All props are declared in the component's `@props` block with defaults:

| Prop | Default | Description |
| --- | --- | --- |
| `icon` | `'fas fa-circle-xmark'` | Font Awesome icon class passed to `<x-icon>`. Rendered at `fa-3x`. Pass a falsy value to hide the icon block. |
| `title` | `__('Nothing to show here')` | The bold empty-state heading (`.empty-title`). |
| `subtitle` | `__('Trust me, I\'ve looked everywhere, there\'s nothing here :(')` | The secondary text (`.empty-subtitle text-secondary`). Pass a falsy value to hide it. |

### Attributes

Any extra HTML attributes are forwarded to the root `<div>`. The component merges your classes onto its default `card` class via `$attributes->class(['card'])`, so the outer element is always a `card` plus whatever classes/attributes you add (e.g. `repeater-empty`, `id`, data-attributes).

### Slots

This component has no named or default slots — content is driven entirely by the `icon`, `title`, and `subtitle` props. There is no `wire:model` or Livewire-specific behavior.

## Markup output

```blade
<div class="card">           {{-- plus any forwarded attributes/classes --}}
    <div class="empty">
        <div class="empty-icon">      {{-- only when $icon is truthy --}}
            <x-icon :icon="$icon" class="fa-3x" />
        </div>
        <p class="empty-title">{{ $title }}</p>
        <p class="empty-subtitle text-secondary">{{ $subtitle }}</p>  {{-- only when $subtitle is truthy --}}
    </div>
</div>
```

## Usage

Default empty state (all defaults), used when a static page has no content:

```blade
@else
    <x-empty />
@endif
```

Custom icon, title, and subtitle, shown when there are no admins to impersonate:

```blade
<x-empty
    icon="fas fa-user-slash"
    :title="__('No admins found')"
    :subtitle="__('You need to create an admin first to be able to impersonate him.')"
/>
```

Forwarding an extra attribute/class — the repeater component renders an empty state with the `repeater-empty` attribute and a custom list icon:

```blade
<x-empty
    repeater-empty
    icon="fas fa-list"
    :title="__('Add items to the list')"
    :subtitle="__('Click the plus button to add a new item')"
/>
```

## Notes & gotchas

- The outer element is always a `card`; any classes you pass are merged onto it rather than replacing it.
- Setting `:icon="false"` (or any falsy value) removes the icon block entirely; likewise `:subtitle="false"` removes the subtitle paragraph. The title always renders.
- The icon is delegated to the [Icon component](/components/icon), which determines how the icon class string is rendered.
