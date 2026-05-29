# Date Picker

`<x-date-picker>` renders a text input wired to the [Tempus Dominus](https://getdatepicker.com/) date/time picker. It ships with an optional label and hint, an input-group calendar icon, and automatically pulls in the Tempus Dominus and Popper assets the first time it is used on a page.

## What it is

The component is backed by `App\View\Components\DatePicker` (which extends the app's base `App\View\Components\Component`) and renders the `resources/components/date-picker.blade.php` view. The Blade markup outputs a Bootstrap `input-group` containing the text input plus a calendar icon, and pushes the picker's CSS and JS into the layout's plugin stacks via `@pushOnce`.

The input carries `init="tempus-dominus"`, which is the hook the platform's asset/init system uses to bootstrap the JS picker on that element. See [Tempus Dominus init](/frontend/inits/tempus-dominus) for the JS side.

## Props

These come from the `DatePicker` constructor:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto `uniqid('date-picker-')` | Element id; also used as the label's `for` target. Generated at render time when not supplied. |
| `title` | `?string` | `null` | Label text. When set, an `<x-label>` is rendered above the input. |
| `hint` | `?string` | `null` | Help text. When set, an `<x-hint>` is rendered below the input. |

### Derived / passthrough attributes

- **`required`** — not a constructor prop. It is computed in `render()` as `str_contains($attributes->get('validation'), 'required')`, so adding `validation="required"` marks the rendered `<x-label>` as required.
- All other attributes are merged onto the `<input>`. The view always merges `class("form-control")` and `init="tempus-dominus"`, so any extra HTML/Livewire attributes you pass (e.g. `name`, `value`, `wire:model`, `validation`, `placeholder`) land directly on the input element.

### Picker behavior attributes (read by the JS init)

The Tempus Dominus init reads attributes off the input to configure the picker. Pass these as plain attributes on `<x-date-picker>`:

- **`datetime`** — enables the clock alongside the calendar; format becomes `yyyy-MM-dd hh:mm T`.
- **`only-time`** — time only; disables the calendar; format becomes `hh:mm T`.
- **`date-*` attributes** — any `data`-style options prefixed `date-` are collected and merged into the picker options.

Default (no flag): calendar only, format `yyyy-MM-dd`, 12-hour clock. The picker theme follows the page's `data-bs-theme` and updates live on the `theme:changed` event.

## Slots

This component has no named or default slot. Content is driven entirely by props/attributes.

## Assets

On first use per page the view pushes:

- `plugins-styles`: `vendor/tempus-dominus/tempus-dominus.min.css`
- `plugins-scripts`: `vendor/popper/popper.min.js` and `vendor/tempus-dominus/tempus-dominus.min.js`

These rely on the layout rendering the `plugins-styles` and `plugins-scripts` stacks and on `hashed_asset()` being available.

## Usage

Basic date picker with a label and an old/model value, as used in the memos form:

```blade
<x-date-picker
    name="date"
    :title="__('Date')"
    :value="old('date', $entry?->date ?? now())"
    datetime
/>
```

Required field (drives the label's required marker):

```blade
<x-date-picker
    name="date"
    :title="__('Date')"
    validation="required"
/>
```

Time-only picker:

```blade
<x-date-picker name="time" :title="__('Time')" only-time />
```

## Gotchas

- `id` is generated lazily; if you need to target the input (e.g. a `<x-label for>` of your own or external JS), pass an explicit `id`.
- The "required" indicator on the label is keyed off the literal string `required` inside the `validation` attribute, not a `required` prop.
- Picker options can be supplied either through `datetime` / `only-time` flags, `date-*` attributes on the element, or the JS init's `options` argument; they are merged in that order of increasing precedence.

## Related

- [Tempus Dominus init](/frontend/inits/tempus-dominus) — the JS that binds `init="tempus-dominus"`.
- [Label component](/components/label) and [Hint component](/components/hint) — rendered for `title` and `hint`.
