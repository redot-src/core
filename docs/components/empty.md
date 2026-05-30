# Empty

`<x-empty>` renders an empty-state placeholder — an icon, a title, and a
subtitle inside a card — for when a list, page, or section has nothing to show.

## Usage

```blade
<x-empty />
```

The defaults read "Nothing to show here"; override them per context.

## Options

- **`icon`** — icon class shown above the text. Pass a falsy value
  (`:icon="false"`) to hide it.
- **`title`** — the bold heading.
- **`subtitle`** — secondary text below the title. Pass a falsy value to hide it.

Extra classes and attributes are merged onto the card.

## Examples

### Custom empty state

```blade
<x-empty
    icon="fas fa-user-slash"
    :title="__('No admins found')"
    :subtitle="__('You need to create an admin first to be able to impersonate him.')"
/>
```

### Empty state with a marker attribute

```blade
<x-empty
    repeater-empty
    icon="fas fa-list"
    :title="__('Add items to the list')"
    :subtitle="__('Click the plus button to add a new item')"
/>
```

## Related

- [Icon](/components/icon) — renders the empty-state icon.
- [Components overview](/components/overview) — attribute pass-through.
