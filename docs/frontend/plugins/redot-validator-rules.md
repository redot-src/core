# RedotValidatorRules

`redot-validator-rules.js` registers the built-in client-side validation rules on the global `RedotValidator` class. It is a companion file to [`redot-validator.js`](/frontend/plugins/redot-validator) — the engine lives there; the rule definitions live here. Together they mirror Laravel's server-side validation so forms can be checked in the browser before submit.

## What it is

The file is a flat list of `RedotValidator.addRule(name, { callback, message })` calls — one per rule. It defines **no global of its own**; it relies on the `RedotValidator` class already being defined by `redot-validator.js`, and on two helpers from `functions.js`: `__()` (translation lookup) and `checkOtherField()` (conditional-rule helper). Each message resolves through `__('validation.<key>', { ... })`, so error text comes from the loaded translation bundle, matching Laravel's `validation` language file.

Load order in `resources/layouts/scaffold.blade.php` is significant — the engine and rules must both be present before any form validation runs:

```blade
<script src="{{ hashed_asset('/assets/plugins/redot-validator.js') }}"></script>
<script src="{{ hashed_asset('/assets/plugins/redot-validator-rules.js') }}"></script>
```

## The callback / message signature

Both the `callback` and `message` functions receive a single destructured argument object (documented in the file header):

| Property | Description |
| --- | --- |
| `value` | The value of the field |
| `params` | Array of parameters parsed from the rule string (e.g. `min:3` → `[3]`) |
| `rules` | The full rules tree applied to the field |
| `name` | The field's `name` attribute |
| `label` | Safe, lowercased name used in the error message |
| `field` | The jQuery field object |
| `type` | The detected field type: `array`, `numeric`, `file`, `date`, or `string` |

`callback` returns `true` when the value passes. `message` returns the localized error string. Note that `file` size rules (`min`/`max`/`size`) compare `file.size` against `params * 1024`, i.e. the parameter is interpreted in **kilobytes**.

## Registered rules

Every rule below is registered via `RedotValidator.addRule(...)`. Parameters come from the rule string after `:` (comma-separated).

| Rule | Parameters | Notes |
| --- | --- | --- |
| `accepted` | — | true for `yes`, `on`, `1`, `1`, `true` (case-insensitive) |
| `accepted_if` | `other, otherValue` | applies `accepted` only when the other field equals `otherValue` |
| `required` | — | non-empty; arrays need length, objects need keys |
| `required_visible` | — | skips validation (sets a `skip` attr) when the field is `:hidden`, otherwise `required` |
| `required_if` | `other, otherValue` | required only when the other field equals `otherValue` |
| `required_unless` | `other, otherValue` | required unless the other field equals `otherValue` |
| `min` | `min` | length / numeric / array-count / file-KB minimum |
| `max` | `max` | length / numeric / array-count / file-KB maximum |
| `between` | `min, max` | composes `min` and `max` |
| `size` | `size` | exact length / value / array-count / file-KB |
| `email` | — | regex match |
| `url` | — | parses with `new URL(value)` |
| `alpha` | — | `^[a-zA-Z]+$` |
| `alpha_num` | — | `^[a-zA-Z0-9]+$` |
| `alpha_dash` | — | `^[a-zA-Z0-9_-]+$` |
| `starts_with` | values… | value starts with any param |
| `ends_with` | values… | value ends with any param |
| `enum` | values… | value loosely equals any param |
| `lowercase` | — | value equals its lowercase form |
| `uppercase` | — | value equals its uppercase form |
| `numeric` | — | `^[0-9]+$` |
| `integer` | — | `value % 1 === 0` |
| `decimal` | — | `value % 1 !== 0` |
| `in` | values… | every value is in params |
| `not_in` | values… | no value is in params |
| `contains` | values… | every param is in value |
| `confirmed` | — | matches `[name="<name>_confirmation"]` in the same form |
| `regex` | `pattern` | strips surrounding slashes, tests with `new RegExp` |

`nullable` is handled by the engine itself (in `redot-validator.js`): if a field has `nullable` and an empty value, all other rules are skipped.

### Conditional rules and `checkOtherField`

`accepted_if`, `required_if`, and `required_unless` delegate to `checkOtherField(field, other, otherValue)` (defined in `functions.js`). It looks up the sibling field within the closest `form` (or `[repeater-item]` for repeaters, matching on `initial-name`) and compares its value loosely (`==`) to `otherValue`.

## How rules are applied

Rules are not invoked directly. They are declared on a field through the `validation` HTML attribute (a pipe-delimited Laravel-style string). The engine's `RedotValidator.getRules()` splits that string, and `validateField()` runs each matching rule's `callback`. Form submission goes through `validateForm($form, verbose)` in `functions.js`, which calls `RedotValidator.errors($form)` and renders messages inline when `verbose` is set.

## Usage

These rules are consumed declaratively via Blade form components such as [`<x-input>`](/components/input). Real examples from the dashboard app:

```blade
{{-- resources/views/dashboard/admins/partials/form.blade.php --}}
<x-input name="name" :title="__('Name')"
         :value="old('name', $entry?->name)"
         :disabled="$entry !== null"
         validation="required" />

<x-input type="email" name="email" :title="__('Email address')"
         :value="old('email', $entry?->email)"
         validation="required|email" />
```

Other real combinations found in the app:

```blade
<x-input validation="required|url" />
<x-input validation="required|min:1" />
<x-input validation="nullable|max:120" />
<x-input validation="nullable|confirmed" />
<x-input validation="nullable|alpha_dash|max:120" />
```

The `radios` component forwards the same attribute, attaching it to the first input and pointing the engine at the group container:

```blade
{{-- resources/components/radios.blade.php --}}
@if ($validation && $loop->first)
    validation="{{ $validation }}"
    validation-container="#{{ $id }}"
@endif
```

## Custom rules

Because rules are just `addRule` registrations, an app can add its own after this file loads:

```js
RedotValidator.addRule('phone', {
    callback: function ({ value }) {
        return /^\+?[0-9]{7,15}$/.test(value);
    },
    message: function ({ label }) {
        return __('validation.phone', { attribute: label });
    },
});
```

## Gotchas

- This file does nothing on its own — it must load **after** `redot-validator.js`, or `RedotValidator.addRule` is undefined.
- File-size rules (`min`/`max`/`size`) treat the parameter as **kilobytes** (`param * 1024`), matching Laravel's file size unit.
- Messages depend on the translation bundle loaded for the current locale; a missing `validation.*` key falls back to the raw key string.
- `decimal` here means "has a fractional part" (`value % 1 !== 0`), which differs from Laravel's `decimal:min,max` semantics.

## Related

- [redot-validator](/frontend/plugins/redot-validator) — the engine that parses the `validation` attribute and runs these rules.
- [Input component](/components/input) — the primary Blade component that exposes the `validation` prop.
