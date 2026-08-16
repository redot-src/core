# RedotRepeater

The engine behind the [`<x-repeater>` component](/components/repeater): a repeatable group of form fields the user can add, remove, reorder, and clear. On submit it serializes the rows into the field as an array (`name[0][field]`, `name[1][field]`, …). Use the component — this plugin is wired up for you by the [`repeater` init](/frontend/inits/repeater).

## Usage

The component's default slot is the template that gets cloned per row. Mark action buttons with an `action` attribute and a drag handle with `sortable-handle`:

```blade
<x-repeater id="contacts" name="contacts" :title="__('Contacts')" :value="old('contacts', $contacts)">
    <div class="card mb-2">
        <div class="card-body">
            <x-input name="name" :title="__('Name')" />
            <x-input name="email" :title="__('Email')" />

            <div class="btn-list mt-2">
                <button type="button" class="btn btn-icon" action="insert"><i class="ti ti-plus"></i></button>
                <button type="button" class="btn btn-icon text-danger" action="remove"><i class="ti ti-trash"></i></button>
                <span sortable-handle class="btn btn-icon cursor-move"><i class="ti ti-grip-vertical"></i></span>
            </div>
        </div>
    </div>
</x-repeater>
```

Server-side you receive `contacts` as an array of objects.

## Action attributes

Put these on buttons inside the template (or, for the static ones, anywhere in the repeater):

- **`action="insert"`** — add a row. Inside an item it inserts after that item; elsewhere it appends to the end.
- **`action="remove"`** — remove the containing row.
- **`action="clear"`** — remove every row.
- **`sortable-handle`** — marks the drag handle for reordering.

## Options

Set these as `repeater-` attributes on the component tag:

- **`repeater-sortable`** — drag-to-reorder, on by default. Set `false` to disable.
- **`repeater-scrollable`** — scroll a newly added row into view (default on).
- **`repeater-confirmable`** — ask for confirmation before remove/clear (default on). Set `false` to skip.
- **`repeater-initial-items`** — number of empty rows to start with.

```blade
<x-repeater id="links" name="links" repeater-sortable="false" repeater-confirmable="false" repeater-initial-items="1">
    <x-input name="url" :title="__('URL')" />
    <button type="button" action="remove" class="btn btn-icon"><i class="ti ti-trash"></i></button>
</x-repeater>
```

## Reacting to changes

The plugin fires jQuery events on the field as rows change — `repeater:inserted`, `repeater:removed`, `repeater:cleared` (each payload includes `{ repeater, item }` where relevant):

```js
$('#contacts').on('repeater:inserted', (e, { item }) => { /* ... */ });
```

Reach the instance later with `$('#contacts').data('repeater')`.

## Gotchas

- Field names inside a row are rewritten to `name[index][field]`; values only land in the field on form submit.
- Conditional-visibility (`visible-when`) inside a row is rewritten per row, so each row's conditions track its own fields — see [Redot Visibility](/frontend/plugins/redot-visibility).

## Related

- [Repeater component](/components/repeater) — the `<x-repeater>` this powers.
- [Repeater init](/frontend/inits/repeater) — the `init="repeater"` that triggers it.
- [Sortable init](/frontend/inits/sortable) — the drag library it reuses.
- [Asset & Init System](/frontend/asset-system) — how the script is loaded and wired.
