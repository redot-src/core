# Repeater

`<x-repeater>` lets the user add, remove, and reorder repeated groups of fields.
Define one item's fields once in the body; the repeater clones that template per
item and serializes the whole list into a hidden input as JSON on submit. Use it
for things like a list of links, phone numbers, or tags.

## Usage

```blade
<x-repeater id="links" name="links" :title="__('Links')" :value="old('links', $entry?->links)">
    <div class="row g-2">
        <div class="col"><x-input name="label" :placeholder="__('Label')" /></div>
        <div class="col"><x-input name="url" :placeholder="__('URL')" /></div>
        <div class="col-auto">
            <button type="button" class="btn btn-icon" action="remove">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</x-repeater>
```

The body is the per-item template. Field `name`s inside are rewritten to indexed
array names automatically (`links[0][label]`, `links[1][label]`, …), so set a
`name` on the repeater for the submitted data, and an `id` when you need it
stable. It shares the
[common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`) and initializes itself through
the [asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown in the toolbar.
- **`hint`** — helper text shown below the component.
- **`value`** — initial items (an array, collection, or JSON string); one item is
  inserted per entry.
- **`id`** — element id; auto-generated when omitted.

Use these markers inside the item template to wire up the toolbar buttons and
drag handle:

- **`action="insert"`** — add a new item after this one.
- **`action="remove"`** — remove this item.
- **`action="clear"`** — clear all items.
- **`sortable-handle`** — restrict drag-reordering to this element.

Plugin behavior can be tuned per element with `repeater-*` attributes (e.g.
`repeater-sortable="false"`, `repeater-initial-items="3"`) — see
[RedotRepeater](/frontend/plugins/redot-repeater). Override the toolbar/list with
a **`wrapper`** slot, or the empty state with an **`empty`** slot.

## Examples

### Pre-filled, non-sortable, required

```blade
<x-repeater
    id="tags"
    name="tags"
    :title="__('Tags')"
    :hint="__('Add at least one tag')"
    validation="required"
    :value="$post->tags"
    repeater-sortable="false"
    repeater-initial-items="1"
>
    <div class="d-flex gap-2" sortable-handle>
        <x-input name="name" :placeholder="__('Tag name')" />
        <button type="button" class="btn btn-icon" action="remove">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</x-repeater>
```

### Custom empty state

```blade
<x-repeater id="members" name="members" :title="__('Members')">
    <x-slot:empty>
        {{ __('No members yet — click the plus button.') }}
    </x-slot:empty>

    <x-input name="email" type="email" :placeholder="__('Email')" />
</x-repeater>
```

## Related

- [Repeater Card](/components/repeater-card) — a ready-made card item with grip and action buttons.
- [RedotRepeater](/frontend/plugins/redot-repeater) — the add/remove/reorder behavior.
- [Repeater init](/frontend/inits/repeater) — how the repeater initializes.
- [Components overview](/components/overview) — shared form-field conventions.
