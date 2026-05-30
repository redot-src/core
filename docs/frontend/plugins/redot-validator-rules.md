# Client-side validation rules

This is the list of rules you can use in a `validation` attribute. They mirror
Laravel's server-side rules, so a string like `required|email|max:120` validates
the same way in the browser as it does on the server. See
[client-side validation](/frontend/plugins/redot-validator) for how to attach
rules to a field and how errors are shown.

## Usage

Write the rules on a form field, pipe-delimited, parameters after a `:`:

```blade
<x-input name="name" :title="__('Name')" validation="required" />
<x-input type="email" name="email" :title="__('Email address')" validation="required|email" />
<x-input name="slug" :title="__('Slug')" validation="nullable|alpha_dash|max:120" />
```

## Rules

- **`accepted`** — value is `yes`, `on`, `1`, or `true`.
- **`accepted_if:other,value`** — accepted only when another field equals `value`.
- **`required`** — non-empty (array length, object keys, or a truthy value).
- **`required_visible`** — required only while the field is visible.
- **`required_if:other,value`** — required when another field equals `value`.
- **`required_unless:other,value`** — required unless another field equals `value`.
- **`nullable`** — optional; an empty value skips the field's other rules.
- **`min:n`** / **`max:n`** / **`between:min,max`** / **`size:n`** — length,
  numeric value, array count, or file size in **kilobytes**, picked by the
  field's type.
- **`email`** — valid email format.
- **`url`** — parses as a URL.
- **`alpha`** / **`alpha_num`** / **`alpha_dash`** — letters / letters+digits /
  letters+digits+`-`+`_`.
- **`starts_with:a,b`** / **`ends_with:a,b`** — value starts/ends with any listed value.
- **`enum:a,b`** / **`in:a,b`** / **`not_in:a,b`** / **`contains:a,b`** — membership checks.
- **`lowercase`** / **`uppercase`** — value equals its lower/upper-cased form.
- **`numeric`** — digits only.
- **`integer`** — whole number. **`decimal`** — has a fractional part.
- **`confirmed`** — matches a `{name}_confirmation` field in the same form.
- **`regex:/pattern/`** — tests against the supplied regex literal.

The conditional rules (`accepted_if`, `required_if`, `required_unless`) resolve
their "other" field inside the same form, and understand repeater rows so a
row's condition tracks that row's own fields.

## Custom rules

Register your own after the page loads — it then works in any `validation`
string just like a built-in:

```js
RedotValidator.addRule('phone', {
    callback: ({ value }) => /^\+?[0-9]{7,15}$/.test(value),
    message: ({ label }) => __('validation.phone', { attribute: label }),
});
```

## Gotchas

- **File-size rules are in kilobytes** (`max:2048` ≈ 2 MB), matching Laravel's unit.
- **`decimal` means "has a fractional part"** here — it does not take Laravel's
  `decimal:min,max` arguments.
- Error text comes from your `validation.*` translation keys; a missing key falls
  back to the raw key string.

## Related

- [Client-side validation](/frontend/plugins/redot-validator) — the `validation` attribute and error display.
- [JS Translations](/frontend/translations) — where rule messages come from.
- [Input](/components/input) — the standard field that exposes `validation`.
