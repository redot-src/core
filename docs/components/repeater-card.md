# Repeater Card

`<x-repeater-card>` is a small presentational wrapper used **inside** a [Repeater](/components/repeater) item template. It renders a Bootstrap-style card with a drag handle and per-item action buttons (insert / remove) that the repeater JS plugin binds to automatically.

## What it is

The component is an **anonymous Blade component** (`resources/components/repeater-card.blade.php`) — there is no backing PHP class in `app/View/Components`. It produces the markup for a single repeatable item: a card whose header carries a sortable grip and the insert/remove controls, and whose body renders the default slot.

The action buttons and the grip are wired up by the `redot-repeater.js` plugin (loaded by the `<x-repeater>` component) through attribute selectors, not by this component itself:

- `action="insert"` — clones the template and inserts a new item after this one.
- `action="remove"` — removes this item.
- `sortable-handle` — marks the drag handle for SortableJS reordering.

This component does not define any `@props`. It exposes only the default slot and forwards attributes.

## API

| Item | Type | Notes |
| --- | --- | --- |
| Default slot (`$slot`) | slot | Rendered inside `.card-body`. Place your repeater item fields here. |
| `class` attribute | merged | Any `class` you pass is merged onto the `.card-body` wrapper via `$attributes->class(['card-body'])`. |
| Other attributes | forwarded | Only `class` is consumed; the component renders a fixed card structure otherwise. |

### Rendered structure

```blade
<div class="card mb-2">
    <div class="card-header">
        <span class="text-muted cursor-grab" sortable-handle>
            <i class="fas fa-grip"></i>
        </span>

        <div class="card-actions">
            <button type="button" class="btn btn-icon" action="insert" title="{{ __('Insert') }}">
                <x-icon icon="fas fa-plus" />
            </button>

            <button type="button" class="btn btn-icon" action="remove" title="{{ __('Remove') }}">
                <x-icon icon="fas fa-times" />
            </button>
        </div>
    </div>

    <div class="card-body">
        {{ $slot }}
    </div>
</div>
```

The `__('Insert')` / `__('Remove')` button titles are localized. Icons are rendered with [`<x-icon>`](/components/icon).

## Usage

`<x-repeater-card>` is meant to be the root element of a repeater's item template — the slot you pass to `<x-repeater>`. The repeater clones this markup for every item, so the card's grip and action buttons get bound per item.

```blade
<x-repeater :id="'phones'" :title="__('Phone numbers')" :value="$phones">
    <x-repeater-card>
        <x-input name="label" :title="__('Label')" />
        <x-input name="number" :title="__('Number')" />
    </x-repeater-card>
</x-repeater>
```

Add classes to the card body when needed (they merge onto `.card-body`):

```blade
<x-repeater :id="'items'">
    <x-repeater-card class="d-flex gap-2">
        <x-input name="title" />
        <x-select name="type" :options="$types" />
    </x-repeater-card>
</x-repeater>
```

## Gotchas

- **No standalone behavior.** The insert/remove buttons and the drag handle only do something when this card lives inside an `<x-repeater>`, because `redot-repeater.js` resolves the `action="insert"`, `action="remove"`, and `[sortable-handle]` selectors relative to the repeater. Used on its own, the buttons are inert.
- **Anonymous component.** There are no constructor props to override; customize only via the slot and the merged `class` attribute.
- **One card per item.** The repeater treats the template's first element as the item wrapper, so use a single `<x-repeater-card>` as the root of the template slot.

## Related

- [Repeater](/components/repeater) — the container that loads the JS plugin and clones this card per item.
- [Icon](/components/icon) — used for the insert/remove button glyphs.
