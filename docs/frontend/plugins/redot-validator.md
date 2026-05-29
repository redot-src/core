# RedotValidator

`RedotValidator` is the dashboard's client-side, Laravel-style form validation engine. It reads pipe-delimited rule strings from a `validation` attribute on form fields, runs registered rule callbacks, and produces localized error messages — mirroring Laravel's server-side validation syntax so the same rule strings work on both sides.

## What it is

`RedotValidator` is a static class (no instances) defined in `public/assets/plugins/redot-validator.js` and loaded globally. Its built-in rule set lives in a companion file, `public/assets/plugins/redot-validator-rules.js`. Both are pulled into every page by the scaffold layout:

```blade
<script src="{{ hashed_asset('/assets/plugins/redot-validator.js') }}"></script>
<script src="{{ hashed_asset('/assets/plugins/redot-validator-rules.js') }}"></script>
```

The class itself is a generic rule runner; it ships with **no rules**. All concrete rules (`required`, `email`, `min`, `confirmed`, …) are registered by `redot-validator-rules.js` via `RedotValidator.addRule()`.

It depends on jQuery (`$`), Lodash (`_`), and a set of helpers defined in `public/assets/js/functions.js` (`getFieldValue`, `stringToPrimitive`, `checkOtherField`, `validateForm`, and the jQuery plugin `$.fn.hasAttr`).

## How fields are bound

`RedotValidator` does not auto-bind on DOM ready. Instead, the global form submit handler in `public/assets/js/app.js` drives it:

```js
// Disable native HTML5 validation
$('form').attr('novalidate', true);

$(document).on('submit', 'form:not([disable-validation])', (event) => {
    event.preventDefault();

    const $form = $(event.target);
    const isValid = validateForm($form, true); // verbose = render errors

    if (isValid) {
        // disable the submit button, show a spinner, then submit natively
        return event.target.submit();
    }
});
```

So validation runs on submit for every `<form>` that does **not** carry the `disable-validation` attribute. `validateForm()` (in `functions.js`) calls `RedotValidator.errors($form)`; if there are errors and `verbose` is true it renders them and scrolls to the first one.

### DOM attributes the engine reads

| Attribute | On | Purpose |
| --- | --- | --- |
| `validation` | field | The rule string, e.g. `validation="required\|email"`. This is the configurable attribute name (`RedotValidator.attribute`). |
| `disable-validation` | form or field | Skips the element entirely. Set on a `<form>` to opt the whole form out; set on a field to skip just that field (`RedotValidator.disableAttribute`). |
| `validation-type` | field | Forces the value type (`string`, `array`, `numeric`, `file`, `date`) instead of auto-detecting. |
| `validation-{rule}-message` | field | Per-field custom message override for a specific rule, e.g. `validation-required-message="…"`. |
| `skip` | field | Set programmatically by a rule (e.g. `required_visible`) to abort validation of that field. Consumed and removed on read. |
| `label` / `title` / `aria-label` | field | Source of the human-friendly attribute name used in messages (first non-empty wins). Falls back to the associated `<label for>` text. |

Error rendering (handled by `appendErrorsToForm` in `functions.js`, not the validator class) additionally reads `validation-container` and `validation-key`, and a few opt-out attributes on the form: `dont-scroll-to-error` and `dont-toast`.

## Public API

All methods are static on `RedotValidator`.

### Static properties

| Property | Default | Description |
| --- | --- | --- |
| `RedotValidator.rules` | `{}` | The registry of rules, keyed by name. |
| `RedotValidator.attribute` | `'validation'` | The DOM attribute that holds rule strings. |
| `RedotValidator.disableAttribute` | `'disable-validation'` | The DOM attribute that excludes an element. |

### `addRule(name, { callback, message })`

Registers a rule. `callback(args)` returns a boolean (`false` = failed). `message(args)` returns the error string (typically via the `__()` translation helper).

The `args` object passed to both functions:

| Key | Description |
| --- | --- |
| `value` | The field value (from `getFieldValue`). |
| `params` | Array of parsed rule parameters (after the `:`, split on `,`, each coerced via `stringToPrimitive`). |
| `rules` | The full parsed rules tree for the field. |
| `name` | The field's `name` attribute. |
| `label` | The sanitized, lowercased display label. |
| `field` | The jQuery field object. |
| `type` | Resolved value type: `array`, `numeric`, `file`, `date`, or `string`. |

```js
RedotValidator.addRule('required', {
    callback: function ({ value, type }) {
        if (type === 'array') return value.length > 0;
        if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0;
        return Boolean(value);
    },
    message: function ({ label }) {
        return __('validation.required', { attribute: label });
    },
});
```

Each invocation receives a `_.cloneDeep` copy of `args`, so mutating it inside a callback does not leak between rules.

### `errors(wrapper)`

Returns an object of `{ fieldName: { ruleName: message } }` for every field matching `[validation]:not([disable-validation])` inside `wrapper`. Returns `{}` when everything passes.

### `validate(wrapper)`

Boolean convenience wrapper: `Object.keys(this.errors(wrapper)).length === 0`.

### `validateField(field, wrapper = 'body')`

Validates a single field and returns its `{ ruleName: message }` errors (empty object if valid). Notable behavior:

- **Type inference** (when `validation-type` is absent): `array` for array values, `numeric` if the field has the `numeric`/`integer` rule, `file` for `type="file"`, `date` for `[init~="date-picker"]`, otherwise `string`.
- **`nullable`**: if the field has the `nullable` rule and the value is empty, the field passes immediately; otherwise `nullable` is stripped before running the remaining rules.
- **`skip`**: if the field has a `skip` attribute, the attribute is removed and validation short-circuits to pass.
- Unknown rules (not in `RedotValidator.rules`) are silently ignored.

### `getRules(field)` / `safeSplit(str, delimiter)`

Internal parsing helpers. `getRules` turns `validation="min:3|in:a,b,c"` into `{ min: [3], in: ['a','b','c'] }`. `safeSplit` is a delimiter-aware splitter that protects regex literals (`/.../`) so that `|` and `,` inside a `regex:` rule are not treated as separators.

## Rule syntax

The `validation` attribute mirrors Laravel: rules are separated by `|`, parameters follow a `:` and are comma-separated.

```
validation="required|email"
validation="nullable|alpha_dash|max:120"
validation="required_if:type,company|min:3"
```

Parameters are coerced by `stringToPrimitive`, so `max:120` yields a numeric `120`, `true`/`false`/`null` become primitives, JSON is parsed, and `window.foo` resolves against the global object.

## Built-in rules

Registered in `redot-validator-rules.js`. Messages resolve through `__('validation.*')` translation keys.

| Rule | Params | Notes |
| --- | --- | --- |
| `accepted` | — | Value is one of `yes`, `on`, `1`, `true`. |
| `accepted_if` | other, value | Accepted only when another field equals `value`. |
| `required` | — | Non-empty (array length, object keys, or truthy). |
| `required_visible` | — | Required only when the field is `:visible`; otherwise sets `skip`. |
| `required_if` | other, value | Required when another field equals `value`. |
| `required_unless` | other, value | Required unless another field equals `value`. |
| `min` / `max` / `between` / `size` | number(s) | Type-aware (array length, numeric value, file size in KB, or string length). |
| `email` | — | Regex email check. |
| `url` | — | Valid via `new URL()`. |
| `alpha` / `alpha_num` / `alpha_dash` | — | Character-class checks. |
| `starts_with` / `ends_with` | values | Any-of prefix/suffix match. |
| `enum` / `in` / `not_in` / `contains` | values | Membership checks. |
| `lowercase` / `uppercase` | — | Casing checks. |
| `numeric` | — | Digits only. |
| `integer` / `decimal` | — | Whole vs. fractional number. |
| `confirmed` | — | Matches a sibling `{name}_confirmation` field in the same form. |
| `regex` | /pattern/ | Tests against the supplied regex literal. |

`accepted_if`, `required_if`, and `required_unless` resolve their "other" field through `checkOtherField`, which also understands repeater items (`[repeater-item]` / `[initial-name]`).

## Usage

### Basic field rules (Blade `x-input`)

Real example from the login view:

```blade
<x-input type="email" name="email" :title="__('Email address')"
    placeholder="your@email.com" validation="required|email" />

<x-input type="password" name="password" :title="__('Password')"
    :placeholder="__('Password')" validation="required" />
```

### Nullable + confirmed (password change)

From the profile edit view — the field is optional, but if filled it must match its `_confirmation` partner:

```blade
<x-input type="password" name="password" :title="__('New Password')"
    validation="nullable|confirmed" />
```

### Parameterized rules

From the shortened-urls form:

```blade
<x-input name="slug" :title="__('Slug')" :value="old('slug', $entry?->slug)"
    validation="nullable|alpha_dash|max:120" :readonly="$entry !== null" />
```

### Conditional / cross-field rules

```blade
<x-input name="name" :title="__('Name')"
    :value="old('name', $entry?->name)" validation="required" />
```

```blade
<x-input name="company_name" validation="required_if:type,company" />
```

### Grouped inputs with a shared container

Checkbox/radio components put the rule on the first input only and point errors at a shared container via `validation-container` (from `resources/components/checkboxes.blade.php`):

```blade
@if ($validation && $loop->first)
    validation="{{ $validation }}" validation-container="#{{ $id }}"
@endif
```

### Opting a form out of validation

Logout/utility forms that submit immediately carry `disable-validation` so the submit handler does not intercept them (from the dashboard navbar):

```blade
<x-form id="logout-form" :action="route('dashboard.logout')" method="POST"
    class="d-none" disable-validation />
```

### Interop with Redot Visibility

When a field is hidden by the [Redot Visibility](/frontend/plugins/redot-visibility) plugin, `app.js` automatically toggles `disable-validation` so hidden fields are not validated:

```js
$(document).on('visibility:updated', '[visible-when]', (event, visible) => {
    const $targets = /* the [validation] element(s) */;
    visible ? $targets.removeAttr('disable-validation') : $targets.attr('disable-validation', true);
});
```

### Validating manually in your own scripts

```js
if (RedotValidator.validate($form)) {
    // all fields pass
}

const errors = RedotValidator.errors($form);
// { email: { email: 'The email must be a valid email address.' }, ... }
```

To add a project-specific rule, register it after the plugin is loaded:

```js
RedotValidator.addRule('phone', {
    callback: ({ value }) => /^\+?\d{7,15}$/.test(value),
    message: ({ label }) => __('validation.phone', { attribute: label }),
});
```

## Error display and events

The validator class only computes errors — it does not touch the DOM beyond reading attributes. Rendering is done by `appendErrorsToForm`/`scrollToFirstError` in `functions.js`, which:

- adds `is-invalid` to the input and `has-invalid-feedback` to its container, and appends a `.invalid-feedback` message,
- flags any enclosing `.input-group` and `.tab-pane` tab,
- scrolls to the first error (unless the form has `dont-scroll-to-error`) and shows a toast (unless `dont-toast`).

`RedotValidator` itself emits **no custom events**. The only event in the flow is the standard form `submit`, intercepted in `app.js`.

## Gotchas

- The validator ships empty — without `redot-validator-rules.js` (or your own `addRule` calls) nothing validates. Both scripts are loaded by the scaffold layout, so this only bites in custom layouts.
- Forms are validated automatically on submit; add `disable-validation` to skip a form, or to a field to skip just that field.
- `nullable` must come first conceptually: an empty nullable field passes regardless of later rules; a filled one runs them with `nullable` removed.
- `confirmed` looks for `{name}_confirmation` inside the field's closest `<form>` — name the partner field accordingly.
- Numeric/file types are auto-detected; override with `validation-type` when detection is wrong (e.g. a numeric-looking string you want validated as text).

## Related

- [Redot Visibility](/frontend/plugins/redot-visibility) — toggles `disable-validation` on hidden fields.
- Input and form Blade components that expose the `validation` prop.
