# Sortable Initializer

`sortable` makes any container's children drag-and-drop reorderable (powered by [SortableJS](https://sortablejs.github.io/Sortable/)). Reach for it for an ad-hoc reorderable list. Repeater and uploader fields handle their own reordering, so you mainly use this for standalone lists.

## Enable it

Mark the container with `init="sortable"`:

```blade
<ul init="sortable" sortable-handle="[data-grip]" sortable-animation="200">
    <li><span data-grip>::</span> Item one</li>
    <li><span data-grip>::</span> Item two</li>
</ul>
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set any [SortableJS option](https://sortablejs.github.io/Sortable/) as a `sortable-` attribute (kebab-case becomes camelCase):

- **`sortable-handle`** — CSS selector for the drag handle; without it the whole row is draggable.
- **`sortable-animation`** — reorder animation duration in ms (defaults to `150`).
- **`sortable-group`** — name a group to drag items between lists.

Reach the instance later with `$('#my-list').data('sortable')`.

> A bare `sortable-handle` attribute with no value is just a marker the [repeater](/frontend/plugins/redot-repeater) reads for its own drag handle — it is not a SortableJS option on its own.

## Related

- [RedotRepeater plugin](/frontend/plugins/redot-repeater) — uses `[sortable-handle]` for per-row drag handles.
- [Repeater init](/frontend/inits/repeater) — its `repeater-sortable` option enables row reordering.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
