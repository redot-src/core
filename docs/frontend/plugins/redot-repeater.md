# RedotRepeater

`RedotRepeater` is the JavaScript class that powers the dashboard's repeatable form-field group. It clones a `<template>`, rewrites field names into an indexed array (`name[0][field]`, `name[1][field]`, …), serializes the result into a hidden input on form submit, and supports drag-to-reorder, add/remove/clear actions, confirmation dialogs, and per-item validation errors. It is driven by the [`<x-repeater>` component](#blade-component) and wired up through the [`repeater` init](#the-repeater-init).

## What it is

The class lives in `public/assets/plugins/redot-repeater.js`. It is loaded on demand: the `<x-repeater>` Blade component pushes its `<script>` tag once via `@pushOnce('plugins-scripts', 'repeater-scripts')`. The class is global (no module export) and is instantiated by the `repeater` init.

It does not register itself on a selector automatically. Instead it is constructed against the component's hidden `<input>` element, walking up to the surrounding `[repeater-container]` to find its parts.

### Vendor libraries it wraps

- **Sortable.js** — `new Sortable(...)` provides drag-and-drop reordering (enabled by default).
- **Lodash (`_`)** — used for option merging (`_.merge`), unique IDs (`_.uniqueId`), and regex escaping.
- **jQuery (`$`)** — DOM traversal, events, and animations.
- It also reads the optional `RedotVisibility.instance` to fix up conditional-visibility selectors inside cloned items, and `window.ErrorsBag` to render server-side validation errors.

## DOM structure & attributes it binds

The class discovers its elements relative to the hidden input passed to the constructor:

| Element | Selector | Role |
| --- | --- | --- |
| Input | the `selector` argument | hidden input holding the serialized JSON value |
| Container | `[repeater-container]` (closest ancestor) | root wrapper |
| Wrapper | `[repeater-wrapper]` (inside container) | holds toolbar + list |
| List | `[repeater-list]` (inside wrapper) | where items are appended; the Sortable root |
| Template | `[repeater-template="<input id>"]` (anywhere) | the markup cloned per item |
| Item | `[repeater-item]` | each rendered item (attribute value is a generated unique id) |
| Empty | `[repeater-empty]` | empty-state element, toggled by item count |

The list/item/template/empty attribute names are configurable via `options.attributes` (see below); the container/wrapper attribute names (`repeater-container`, `repeater-wrapper`) are hard-coded.

### Action selectors

Buttons are bound by an `action` attribute (default selectors in `options.actions`):

- `[action="insert"]` — add a new item. Static buttons (outside any item) append to the end; buttons inside an item insert after that item.
- `[action="remove"]` — remove the containing item (item-scoped only).
- `[action="clear"]` — remove all items.

Static insert/clear buttons are ignored if they sit inside a `[repeater-item]` (so cloned templates don't double-bind them).

## Constructor

```js
new RedotRepeater(selector, options = {})
```

- `selector` — the hidden input (string selector or element); the class reads its `id` (used as the repeater identifier and to match `[repeater-template="<id>"]`) and its `name` (used as the field-name prefix).
- `options` — merged over the defaults via `_.merge`.

On construction it: restores existing data from the input value, inserts `initialItems` if the list is empty, then calls `init()`.

## Options

| Option | Default | Description |
| --- | --- | --- |
| `sortable` | `true` | `true`/`false`, or a Sortable options object that is merged with the internal defaults (`animation: 150`, `filter: [repeater-empty]`, `onEnd → reorder`). If the template contains `[sortable-handle]`, it is set as the drag `handle`. |
| `scrollable` | `true` | When true, smooth-scrolls to a newly inserted item (skipped for silent inserts). |
| `confirmable` | `true` | `true`/`false`, or a jQuery-Confirm options object. When truthy, `remove`/`clear` show a confirmation via `warnBeforeAction` before acting. |
| `initialItems` | `0` | Number of empty items inserted on init when the list starts empty. |
| `itemTag` | `'div'` | Tag name for the cloned item wrapper element. |
| `animations.insert` | `{ effect: 'show', duration: 0 }` | jQuery animation method + ms for insertion. |
| `animations.remove` | `{ effect: 'hide', duration: 0 }` | jQuery animation method + ms for removal. |
| `actions.insert` | `'[action="insert"]'` | Insert button selector. |
| `actions.remove` | `'[action="remove"]'` | Remove button selector. |
| `actions.clear` | `'[action="clear"]'` | Clear button selector. |
| `attributes.template` | `'repeater-template'` | Template attribute name. |
| `attributes.list` | `'repeater-list'` | List attribute name. |
| `attributes.item` | `'repeater-item'` | Item attribute name. |
| `attributes.empty` | `'repeater-empty'` | Empty-state attribute name. |

## Public methods

- `init()` — binds static action buttons, sets up Sortable, binds the form `submit` handler that serializes items into the input, appends `window.ErrorsBag` errors scoped to this repeater's `name`, and fires `repeater:initialized`.
- `insert(data = {}, after = null, silent = false)` — clones the template, assigns a unique `[repeater-item]` id, fixes duplicate `id`/`for`/`href`/`aria-*`/`data-target` references, rewrites field names, binds item events, appends (or inserts after a given element), reorders, deserializes `data` into fields, optionally scrolls, and fires `repeater:inserted`.
- `remove(item, force = false)` — removes an item (with confirmation unless `force` or `confirmable === false`); fires `repeater:remove` then `repeater:removed`.
- `clear(force = false)` — removes all items (with confirmation); fires `repeater:clear` then `repeater:cleared`. No-op when already empty.
- `reorder()` — renumbers every field name to `name[<index>][...]` and toggles the empty-state element.
- `get()` — returns an array of item objects (each via `serializeFields($item, 'initial-name')`).
- `set(items)` — accepts a JSON string or array and inserts each item silently.
- `trigger(name, data = {})` — fires `repeater:<name>` on the input with `{ repeater: this, ...data }` merged in.

### Field-name rewriting

When an item is cloned, every `[name]` field's original name is stored in an `initial-name` attribute, and the live name becomes `name[__index__][first]<rest>`. `reorder()` then replaces `__index__` (or any prior numeric index) with the actual position. On `get()`/serialization the `initial-name` is used as the key, so the serialized JSON uses the original field names without the array prefix.

## Events

All events are jQuery events fired on the hidden input, namespaced `repeater:`. Every payload includes `{ repeater }`:

| Event | Extra payload | When |
| --- | --- | --- |
| `repeater:initialized` | — | after `init()` |
| `repeater:inserted` | `{ item, data }` | after an item is inserted |
| `repeater:remove` | `{ item }` | before an item is removed |
| `repeater:removed` | `{ item }` | after removal animation completes |
| `repeater:clear` | — | before clearing all |
| `repeater:cleared` | — | after clearing all |

```js
$('#my-repeater').on('repeater:inserted', (e, payload) => {
    console.log(payload.item, payload.data);
});
```

## The `repeater` init

`public/assets/inits/repeater.js` is registered as `window.__inits.repeater`. The global [`init()` helper](#) scans for `[init]` elements and calls the matching initiator. The init reads `repeater-`prefixed attributes off the element (via `getOptionsFromSelector(selector, 'repeater-')`), merges them over `{ sortable: true, scrollable: true, confirmable: true }`, constructs the instance, and stores it on the element with `$(selector).data('repeater', repeater)`:

```js
return (selector, options = {}) => {
    const defaultOptions = { sortable: true, scrollable: true, confirmable: true };
    const selectorOptions = getOptionsFromSelector(selector, 'repeater-');
    options = _.merge(defaultOptions, selectorOptions, options);

    const repeater = new RedotRepeater(selector, options);
    $(selector).data('repeater', repeater);
};
```

So you can override options declaratively, e.g. `repeater-sortable="false"` or `repeater-initial-items="1"` on the input (the init camel-cases dashed keys, so `repeater-initial-items` becomes `initialItems`). To reach the instance later: `$('#my-repeater').data('repeater')`.

## Blade component

`<x-repeater>` (`app/View/Components/Repeater.php` + `resources/components/repeater.blade.php`) renders the full structure and pushes the plugin script. Its hidden input carries `init="repeater"`, so the [init system](#the-repeater-init) wires up `RedotRepeater` automatically.

### Props (constructor args)

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('repeater-')` | input id; also the template match key. |
| `title` | `?string` | `null` | toolbar label (rendered via `<x-label>`). |
| `hint` | `?string` | `null` | help text rendered via `<x-hint>`. |
| `value` | `array\|string\|Collection\|null` | `null` | initial data; arrays/Collections are JSON-encoded into the input. |

A `required` flag is derived in `render()` from whether the merged `validation` attribute contains `required`. Extra attributes are merged onto the hidden input (alongside `init="repeater"`), so declarative options like `repeater-sortable="false"` go on the tag.

### Slots

- **Default slot** — the per-item template markup (rendered inside `<template repeater-template="{id}">`).
- `wrapper` — replace the entire toolbar + list (you must then provide your own `[repeater-list]` and action buttons).
- `empty` — custom empty-state markup (gets `repeater-empty`). Defaults to an `<x-empty>` placeholder.

## Usage

```blade
<x-repeater
    id="contacts"
    name="contacts"
    :title="__('Contacts')"
    :hint="__('Add one or more contacts')"
    :value="old('contacts', $contacts)"
>
    <div class="card mb-2">
        <div class="card-body">
            <x-input name="full_name" :title="__('Full name')" />
            <x-input name="email" :title="__('Email')" />

            <div class="btn-list mt-2">
                <button type="button" class="btn btn-icon" action="insert">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn btn-icon text-danger" action="remove">
                    <i class="fas fa-trash"></i>
                </button>
                <span sortable-handle class="btn btn-icon cursor-move">
                    <i class="fas fa-grip-vertical"></i>
                </span>
            </div>
        </div>
    </div>
</x-repeater>
```

Disable sorting / confirmation and seed one empty item declaratively:

```blade
<x-repeater id="links" name="links"
    repeater-sortable="false"
    repeater-confirmable="false"
    repeater-initial-items="1">
    <x-input name="url" :title="__('URL')" />
    <button type="button" action="remove" class="btn btn-icon"><i class="fas fa-trash"></i></button>
</x-repeater>
```

> Note: no `<x-repeater>` usages exist in the dashboard's `resources/` views at the time of writing; the examples above reflect the component's real props, attributes, and the action/handle selectors the plugin binds.

## Gotchas

- **The item template must include the field name fields** — names are rewritten to `name[index][field]`; on submit the hidden input is filled with the serialized JSON and the original fields keep their indexed names. Server-side you receive `name` as an array of objects.
- **Validation errors** rely on `window.ErrorsBag`; only keys starting with the repeater's `name` are matched and appended via `appendErrorsToForm`.
- **Confirmation dialogs** use the shared `warnBeforeAction` helper (jQuery-Confirm). Set `confirmable` to `false` (or pass options) to customize.
- **Duplicate ids** are auto-prefixed with the item's unique id inside clones; references in `for`, `href`, `aria-controls/labelledby/describedby`, `data-target`, `data-bs-target`, and the repeater attributes are rewritten so nested repeaters and labels keep working.
- The container/wrapper attribute names are not configurable — only template/list/item/empty are.

## Related

- [Sortable init](/frontend/inits/sortable)
- [Uploader component](/components/uploader)
