# Sortable Initializer

The sortable initializer is a small factory that wraps [SortableJS](https://sortablejs.github.io/Sortable/) and turns any element into a drag-and-drop reorderable list. It is one of the initiator factories registered on `window.__inits` and is consumed through the dashboard's declarative `init="..."` attribute system.

## What it is

The file at `public/assets/inits/sortable.js` exports a factory function `(selector, options = {}) => Sortable`. When the platform's `init()` routine encounters an element marked with `init="sortable"`, it calls this factory with that element and any inline options, constructs a new SortableJS instance, and stashes it on the element via jQuery `.data('sortable', ...)`.

The vendor library itself (the global `Sortable` constructor) is loaded separately in the scaffold layout:

```blade
<script src="{{ hashed_asset('/vendor/sortable/sortable.min.js') }}"></script>
```

## How it works

```js
return (selector, options = {}) => {
    const defaultOptions = {
        animation: 150,
    };

    const selectorOptions = getOptionsFromSelector(selector, 'sortable-');
    options = _.merge(defaultOptions, selectorOptions, options);

    const sortable = new Sortable($(selector).get(0), options);

    // Set the instance on the input element.
    $(selector).data('sortable', sortable);

    return sortable;
};
```

Behavior, in order:

1. Starts from a single default option, `animation: 150` (SortableJS animation duration in ms).
2. Reads extra options off the element's attributes using `getOptionsFromSelector(selector, 'sortable-')`. Every attribute prefixed with `sortable-` becomes an option key (kebab-case is camel-cased, values are coerced to primitives).
3. Merges, in increasing precedence: defaults → selector attributes → options passed by the caller (`_.merge(defaultOptions, selectorOptions, options)`).
4. Constructs `new Sortable(el, options)` and stores the instance on the element with `.data('sortable', sortable)` so other code can retrieve it later.

There is no locale or RTL handling in this initializer; it passes options straight through to SortableJS.

## Options

| Source | How it is supplied | Notes |
| --- | --- | --- |
| `animation` | default | `150`. Always applied unless overridden. |
| `sortable-*` attributes | element attributes | Any [SortableJS option](https://sortablejs.github.io/Sortable/) can be set declaratively, e.g. `sortable-handle="[data-handle]"`, `sortable-animation="200"`. Keys are camel-cased. |
| inline `init` payload | `init="sortable"` value attribute | The platform passes `this.hasAttribute('sortable') ? stringToPrimitive($(this).attr('sortable')) : {}` as the `options` argument; highest precedence. |

Because options come from `getOptionsFromSelector`, the `sortable-handle` attribute on its own (with no value) does NOT configure SortableJS — it is a plain marker attribute used by the repeater plugin (see below).

## Usage

Mark a container with `init="sortable"`; the platform's `init()` walks `[init]:not([initialized])` elements on load and runs the matching factory:

```blade
<ul init="sortable" sortable-handle="[data-grip]" sortable-animation="200">
    <li><span data-grip>::</span> Item one</li>
    <li><span data-grip>::</span> Item two</li>
</ul>
```

To read the SortableJS instance back from the element:

```js
const instance = $('#my-list').data('sortable');
```

## Relationship to the repeater

In practice, sortable lists are most often driven by the [Repeater plugin](/frontend/plugins/redot-repeater), which builds its own SortableJS instance internally rather than going through this initializer. The repeater wires drag-reordering when its `sortable` option is enabled (the default) and looks for a `[sortable-handle]` marker inside each item template to use as the drag handle:

```js
// from redot-repeater.js
if ($(this.$template.html()).find('[sortable-handle]').length) {
    defaultSortableOptions.handle = '[sortable-handle]';
}
```

The dashboard's repeater card component renders exactly such a handle:

```blade
{{-- resources/components/repeater-card.blade.php --}}
<div class="card mb-2">
    <div class="card-header">
        <span class="text-muted cursor-grab" sortable-handle>
            <i class="fas fa-grip"></i>
        </span>
        ...
    </div>
    <div {{ $attributes->class(['card-body']) }}>
        {{ $slot }}
    </div>
</div>
```

So `sortable-handle` appears in the live app primarily as the grip marker consumed by the repeater, while this standalone `sortable` initializer is the general-purpose hook for ad-hoc reorderable lists.

## Gotchas

- The vendor `Sortable` global must be present (loaded via the scaffold layout). If it is missing the factory throws.
- `getOptionsFromSelector` only reads attributes that start with `sortable-`; a bare `sortable-handle` (no value) is ignored by this initializer.
- The instance is stored on the element via jQuery `.data('sortable', ...)`, not as a DOM property — read it with `$(el).data('sortable')`.

## Related

- [Repeater plugin](/frontend/plugins/redot-repeater) — builds SortableJS internally and uses `[sortable-handle]` for drag handles.
- [Repeater initializer](/frontend/inits/repeater) — the `init="repeater"` factory whose `sortable` option (default `true`) enables item reordering.
