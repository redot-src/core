# Repeater

`<x-repeater>` renders a repeatable group of form fields: a toolbar with add/clear buttons, a sortable list of items cloned from a `<template>`, and a hidden input that serializes the list into JSON on form submit. The Blade component only renders the markup and pushes the plugin script; all behavior (add/remove/reorder, name rewriting, validation errors) is driven by the [`RedotRepeater` plugin](/frontend/plugins/redot-repeater) via the [`repeater` init](/frontend/inits/repeater).

## What it is

A class-based component: `App\View\Components\Repeater` (extending the app's base `App\View\Components\Component`) with the view `resources/components/repeater.blade.php`. The view:

- Pushes the slot into a <span v-pre>`<template repeater-template="{{ $id }}">`</span> (the per-item markup that gets cloned).
- Renders a `<div repeater-container>` holding a hidden <span v-pre>`<input id="{{ $id }}" value="{{ $value }}" init="repeater">`</span>.
- Renders a default toolbar (label + insert/clear buttons) and an empty-state list, unless you override them via slots.
- Pushes the plugin `<script>` once via `@pushOnce('plugins-scripts', 'repeater-scripts')`.

Because the hidden input carries `init="repeater"`, the dashboard init system instantiates `RedotRepeater` on it automatically.

## Props

From the `Repeater` constructor:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('repeater-')` | DOM id of the hidden input. Also links the `<template repeater-template="{id}">` to the input and the `<x-label for="{id}">`. Pass an explicit value to keep it stable. |
| `title` | `?string` | `null` | When set, renders an `<x-label :title :for="$id" :required>` in the toolbar. Omit it (or use a `wrapper` slot) for no label. |
| `hint` | `?string` | `null` | When set, renders an `<x-hint class="mt-1">` below the component. |
| `value` | `array\|string\|Collection\|null` | `null` | Initial items. Arrays and `Collection`s are JSON-encoded (`JSON_UNESCAPED_UNICODE`) into the hidden input; a string is used as-is. The plugin parses this on init and inserts one item per entry. |

### Derived / attribute-driven props

- `required` — not a constructor argument. The view receives it from `render()`, which computes it as `str_contains($this->attributes->get('validation'), 'required')`. So adding `validation="required"` (or any `validation` containing `required`) marks the label required.
- All other attributes are merged onto the hidden input via <span v-pre>`{{ $attributes->merge(['init' => 'repeater']) }}`</span>. This is how plugin options reach the instance: `repeater-*` attributes (e.g. `repeater-sortable="false"`, `repeater-initial-items="3"`) are read by the init. See [Repeater Initializer](/frontend/inits/repeater) for the full attribute list.

> Note: the component has no `name` prop. The plugin reads `name` from the hidden input's `name` attribute (set via the attribute bag) and uses it as the array prefix when rewriting field names to `name[0][field]`, `name[1][field]`, etc.

## Slots

| Slot | Purpose |
| --- | --- |
| default (`$slot`) | The per-item template markup. Cloned for every item. Field `name`s inside are rewritten to indexed array names by the plugin. |
| `wrapper` | Replaces the entire default toolbar + list. Provide your own markup containing `[repeater-list]`, the `[action="insert"]` / `[action="clear"]` buttons, and an `[repeater-empty]` element. |
| `empty` | Replaces the default `<x-empty>` empty-state shown when the list has no items. Its attributes are merged with `repeater-empty`. |

When neither slot is given, the default toolbar renders insert (`<i class="fas fa-plus">`) and clear (`<i class="fas fa-trash">`) buttons, and the empty state uses `<x-empty icon="fas fa-list">`.

## Item markup conventions

Inside the default slot (the template), the plugin recognizes these markers/selectors:

- `[action="insert"]` — add a new item after this one.
- `[action="remove"]` — remove this item (asks for confirmation when `confirmable`).
- `[action="clear"]` — clear all items.
- `[sortable-handle]` — if present, drag-reorder is restricted to this handle.

## Usage

> No `<x-repeater>` usages exist in the dashboard's `resources/` views at the time of writing. The examples below reflect the component's real props/attributes and the action/handle selectors the plugin binds.

Basic repeater with a title and a two-field item template:

```blade
<x-repeater id="links" :title="__('Links')">
    <div class="row g-2">
        <div class="col">
            <x-input name="label" :placeholder="__('Label')" />
        </div>
        <div class="col">
            <x-input name="url" :placeholder="__('URL')" />
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-icon" action="remove">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</x-repeater>
```

Pre-filled, with plugin options passed as attributes and a required label:

```blade
<x-repeater
    id="tags"
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

Custom empty state via the `empty` slot:

```blade
<x-repeater id="members" :title="__('Members')">
    <x-slot:empty>
        {{ __('No members yet — click the plus button.') }}
    </x-slot:empty>

    <x-input name="email" type="email" :placeholder="__('Email')" />
</x-repeater>
```

## How it serializes

On form submit the plugin clears the hidden input, collects every `[repeater-item]`, serializes each into an object keyed by the item's `initial-name`s, and writes the JSON array back to the hidden input. So `id="links"` (with `name="links"`) submits as `links` = `[{"label":"...","url":"..."}, ...]`. An empty list submits nothing for that field.

## Gotchas

- Set an explicit `id` (and a `name`) when you need stable form data or server-side validation keys; otherwise `id` is a random `uniqid('repeater-')`.
- The `required` star comes from the `validation` attribute, not a dedicated prop.
- Structural marker attributes (`repeater-container`, `repeater-wrapper`, `repeater-list`, `repeater-template`, `repeater-item`, `repeater-empty`) live on the component's own elements — do not confuse them with the `repeater-*` option attributes you place on `<x-repeater>` itself.

## Related

- [RedotRepeater plugin](/frontend/plugins/redot-repeater) — the JS class powering this component (add/remove/reorder, name rewriting, validation errors).
- [Repeater Initializer](/frontend/inits/repeater) — how the hidden input's `init="repeater"` and `repeater-*` attributes are wired up.
- [Components overview](/components/overview)
