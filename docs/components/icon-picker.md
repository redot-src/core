# Icon Picker

`<x-icon-picker>` renders a text input bound to a FontAwesome icon, with a live preview addon and a "search" button that opens a modal. The modal queries the FontAwesome GraphQL API for free icons, lets the user pick one, and writes the icon class string (e.g. `far fa-note-sticky`) back into the input.

## What it is

The component is backed by the class `App\View\Components\IconPicker` (view `components.icon-picker`). The rendered markup is wired up by a client-side plugin, `RedotIconPicker`, shipped at `public/assets/plugins/redot-icon-picker.js`. The input carries an `init="icon-picker"` attribute that the dashboard's plugin auto-init system uses to instantiate the picker.

## Props

The constructor of `App\View\Components\IconPicker` exposes these props:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto `uniqid('icon-picker-')` | DOM id for the input. Auto-generated when omitted. |
| `title` | `?string` | `null` | Label text. When set, an `<x-label>` is rendered above the input (with `:for` and `:required`). |
| `hint` | `?string` | `null` | Help text rendered below the input via `<x-hint>`. |

There is **no** explicit `required` prop. The view receives a computed `$required` flag, derived in `render()` from the `validation` attribute:

```php
'required' => str_contains($this->attributes->get('validation') ?: '', 'required'),
```

So adding `validation="required"` both drives server-side validation conventions and marks the label as required.

### Attributes

All other HTML attributes pass through to the `<input>` via the attribute bag (`$attributes->class(['form-control'])->merge(['init' => 'icon-picker'])`). Notable ones used in practice:

- `name` — the form field name submitted with the form.
- `value` — the current icon class string. It is read in the Blade view both for the input and to seed the preview icon class: <span v-pre>`<i class="icon icon-sm {{ $attributes->get('value') }}" ...>`</span>.
- `validation` — see above; `required` toggles the required marker.
- `init` — defaulted to `icon-picker`; this is what triggers the JS plugin. Do not override unless you know the init system.

## Structure / required DOM

The view pushes a `<template iconpicker-template>` once (stack `templates`) containing the modal's search input, list container, empty state, and loading spinner. The interactive root is `<div iconpicker-wrapper>`, which holds:

- `[iconpicker-preview]` — the `<i>` whose class mirrors the selected icon.
- the `<input init="icon-picker">`.
- `[iconpicker-picker]` — the search button that opens the modal.

The plugin script is pushed once to the `plugins-scripts` stack (key `icon-picker-scripts`):

```blade
<script src="{{ hashed_asset('assets/plugins/redot-icon-picker.js') }}"></script>
```

## JS behavior (RedotIconPicker)

`RedotIconPicker` is constructed with `(selector, options)`. On init it locates the wrapper, preview, and picker button relative to the input, binds events, and triggers an initial `change` to sync the preview.

Default options (from the source):

| Option | Default | Description |
| --- | --- | --- |
| `endpoint` | `https://api.fontawesome.com` | FontAwesome GraphQL API base. |
| `version` | `6.4.2` | FontAwesome version queried. |
| `maxResults` | `100` | Max icons fetched per search. |
| `searchDebounce` | `100` | Debounce (ms) on the modal search field. |
| `attributes` | object | Attribute names used to find DOM hooks: `template`, `modal`, `search`, `list`, `icon`, `empty`, `loading`. |

Behavior:

- Typing/changing the input updates the preview class: `icon icon-sm <value>`.
- Clicking the picker button opens a `$.confirm` modal (jQuery Confirm) seeded from the `iconpicker-template` HTML, with Cancel and Select buttons.
- The modal search field calls the FontAwesome `search(version, query, first)` GraphQL query (debounced) and populates `[iconpicker-icon="fa-<style> fa-<id>"]` entries; clicking one marks it `.selected`.
- Pressing Select writes the chosen class to the input via `.val(...).trigger('change')`, which in turn updates the preview. If nothing is selected, it is a no-op.

It depends on jQuery, lodash (`_.merge`, `_.debounce`), and jQuery Confirm (`$.confirm`) being present globally.

## Usage

Real usage from the memos form (`resources/views/dashboard/memos/partials/form.blade.php`):

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'far fa-note-sticky')" />
```

With a hint and required validation:

```blade
<x-icon-picker
    name="icon"
    :title="__('Icon')"
    :hint="__('Pick a FontAwesome icon')"
    validation="required"
    :value="old('icon')"
/>
```

## Gotchas

- The stored value is a full FontAwesome class string (style + name, e.g. `far fa-note-sticky`), not just an icon id. Seed `:value` with that full string.
- Only **free** icons are returned by the search (icons with no free style are skipped).
- The picker requires network access to `api.fontawesome.com`; on failure the modal shows its empty state.
- Both the template and the plugin script are pushed with `@pushOnce`, so multiple pickers on one page share a single template and a single script include.

## Related

- [Label component](/components/label)
- [Hint component](/components/hint)
