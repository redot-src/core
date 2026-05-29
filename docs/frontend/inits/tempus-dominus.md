# Tempus Dominus Initializer

The `tempus-dominus` initializer wires a text input up to the [Tempus Dominus](https://getdatepicker.com/) date/time picker. It is the JavaScript engine behind the [Date Picker component](/components/date-picker), reading configuration from the input's attributes and adapting to the page theme and text direction.

## What It Is

`public/assets/inits/tempus-dominus.js` registers an initializer that the global init system (`window.__inits['tempus-dominus']`) invokes for every element carrying `init="tempus-dominus"`. It builds a sensible default option set, merges in attribute-driven and caller-supplied options, instantiates `tempusDominus.TempusDominus` on the element, and keeps the widget's theme in sync with the dashboard's light/dark toggle.

It wraps the vendor library loaded by the `<x-date-picker>` component:

```blade
<script src="{{ hashed_asset('/vendor/tempus-dominus/tempus-dominus.min.js') }}"></script>
```

## Signature

```js
(selector, options = {}) => picker
```

- `selector` — the element to attach to (passed automatically by the init runner as the DOM node).
- `options` — an optional object merged on top of the defaults and the attribute-derived options.

The returned `TempusDominus` instance is also stored on the element via jQuery data:

```js
$picker.data('picker', picker);
```

So you can retrieve it later with `$('#my-input').data('picker')`.

## Auto-Initialization

The init runner in `functions.js` scans for elements with an `init` attribute and calls each named initializer. The `<x-date-picker>` component emits the trigger attribute:

```blade
{{ $attributes->class(['form-control'])->merge(['init' => 'tempus-dominus']) }}
```

Per-element options can also be supplied inline as the value of the `tempus-dominus` attribute itself (parsed by the init runner via `stringToPrimitive`).

## Default Options

```js
{
    display: {
        theme: $('html').attr('data-bs-theme'),   // follows the page theme
        components: {
            calendar: true,
            clock: false,
        },
        icons: {
            // direction-aware chevrons (see RTL handling below)
            previous: 'fa-solid fa-chevron-left',
            next: 'fa-solid fa-chevron-right',
        },
    },
    localization: {
        format: 'yyyy-MM-dd',
        hourCycle: 'h12',
    },
}
```

These defaults produce a date-only picker formatted as `yyyy-MM-dd` in 12-hour clock mode.

## Attribute-Driven Options (`date-*`)

Beyond the inline `tempus-dominus` value, the initializer reads any attribute prefixed with `date-` off the element and merges it into the options:

```js
const selectorOptions = getOptionsFromSelector(selector, 'date-');
options = _.merge(defaultOptions, selectorOptions, options);
```

`getOptionsFromSelector` strips the `date-` prefix, converts the remaining key to camelCase, and supports dotted/nested keys via `_.set`. For example, `date-localization-format="dd/MM/yyyy"` sets `localization.format`. Precedence (lowest to highest): defaults → `date-*` attributes → the `options` argument.

## Mode Attributes

Two boolean attributes (or an equivalent `type` option) switch the picker between date, datetime, and time-only modes:

| Attribute | `type` value | Effect |
| --- | --- | --- |
| `datetime` | `datetime` | Enables both calendar and clock; format becomes `yyyy-MM-dd hh:mm T` |
| `only-time` | `time` | Disables calendar, enables clock; format becomes `hh:mm T` |
| _(none)_ | — | Calendar only; format stays `yyyy-MM-dd` |

```js
if ($picker.hasAttr('datetime') || options.type === 'datetime') {
    options.display.components.clock = true;
    options.display.components.calendar = true;
    options.localization.format = 'yyyy-MM-dd hh:mm T';
}

if ($picker.hasAttr('only-time') || options.type === 'time') {
    options.display.components.calendar = false;
    options.display.components.clock = true;
    options.localization.format = 'hh:mm T';
}
```

The `type` key is deleted from the options before instantiation (`delete options.type`), since it is only a convenience shorthand and not a real Tempus Dominus option.

## RTL Handling

The initializer reads the computed `direction` of the element and flips the navigation chevrons so "previous" and "next" point the correct way in right-to-left layouts:

```js
const isRtl = getComputedStyle($picker.get(0)).direction === 'rtl';

icons: {
    previous: isRtl ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left',
    next: isRtl ? 'fa-solid fa-chevron-left' : 'fa-solid fa-chevron-right',
}
```

## Theme Synchronization

The picker's initial theme comes from the `data-bs-theme` attribute on `<html>`. The initializer also listens for the dashboard's `theme:changed` event, hides the open widget, and pushes the new theme into the live instance:

```js
document.addEventListener('theme:changed', () => {
    const theme = $('html').attr('data-bs-theme');
    picker.hide();
    picker.updateOptions({ display: { theme: theme } });
});
```

## Usage

In practice you use the [Date Picker component](/components/date-picker) rather than calling the initializer directly. A real example from the dashboard's memo form:

```blade
<x-date-picker name="date" :title="__('Date')" :value="old('date', $entry?->date ?? now())" datetime />
```

The `datetime` attribute flows through to the input and switches the picker into combined date-and-time mode. A date-only picker is simply:

```blade
<x-date-picker name="published_at" :title="__('Published at')" />
```

To override the display format on a single field, add a `date-*` attribute:

```blade
<x-date-picker name="dob" :title="__('Date of birth')" date-localization-format="dd/MM/yyyy" />
```

## Gotchas

- The element must be a single DOM node; `getComputedStyle($picker.get(0))` will throw if the selector matches nothing, so the input has to exist in the DOM when the initializer runs.
- `datetime` and `only-time` are mutually exclusive in intent; if both are present, `only-time` wins because its block runs last and disables the calendar.
- `theme:changed` listeners are added per initialized picker and are not removed, so re-initializing the same element repeatedly will stack listeners.

## Related

- [Date Picker component](/components/date-picker) — the Blade component that loads the vendor assets and emits the `init="tempus-dominus"` trigger.
- The same Tempus Dominus library is reused by the query builder's `$.fn.datepicker` helper (`public/assets/inits/query-builder.js`), which delegates to `window.__inits['tempus-dominus']`.
