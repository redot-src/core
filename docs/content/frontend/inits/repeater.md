# Repeater Initializer

`repeater` bootstraps the [RedotRepeater plugin](/frontend/plugins/redot-repeater) on a field, turning a template into a repeatable group of rows the user can add, remove, reorder, and clear. It backs the [`<x-repeater>` component](/components/repeater).

## Enable it

You don't enable this by hand — render the [`<x-repeater>` component](/components/repeater), whose hidden field carries `init="repeater"` and whose default slot is the per-row template:

```blade
<x-repeater id="links" :title="__('Links')" name="links">
    <x-input name="label" :title="__('Label')" />
    <x-input name="url" :title="__('URL')" />
    <button type="button" action="remove" class="btn btn-icon"><i class="ti ti-trash"></i></button>
</x-repeater>
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set these as `repeater-` attributes on the component tag:

- **`repeater-sortable`** — drag-to-reorder rows (on by default). Set `false` to disable.
- **`repeater-scrollable`** — scroll a newly added row into view (default on).
- **`repeater-confirmable`** — confirm before remove/clear (default on). Set `false` to skip.
- **`repeater-initial-items`** — number of empty rows to start with.

```blade
<x-repeater id="tags" :title="__('Tags')" repeater-sortable="false" repeater-initial-items="1" />
```

The full set of behaviors, action attributes, and events lives on the plugin page.

## Related

- [RedotRepeater plugin](/frontend/plugins/redot-repeater) — action attributes, events, and the instance API.
- [Repeater component](/components/repeater) — the component that enables this for you.
- [Sortable init](/frontend/inits/sortable) — the drag library it reuses.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
