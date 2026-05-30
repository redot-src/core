# Client-side validation

Every form field can carry a `validation` attribute holding a pipe-delimited,
Laravel-style rule string. The dashboard validates the form in the browser on
submit — before it ever hits the server — and shows inline errors. The rule
syntax mirrors Laravel's, so the same string works on both sides. This is the
canonical reference for the `validation` attribute.

## Usage

Put the rules on the field (the form components expose a `validation` prop):

```blade
<x-input type="email" name="email" :title="__('Email address')" validation="required|email" />

<x-input type="password" name="password" :title="__('Password')" validation="required" />
```

Validation runs automatically on submit for every `<form>` on the page — you do
not wire anything up. Errors are rendered inline next to each field, the first
one is scrolled into view, and a toast summarizes them. Native HTML5 validation
is disabled in favour of this engine.

## Rule syntax

Rules are separated by `|`; a rule's parameters follow a `:` and are
comma-separated — exactly like Laravel:

```blade
<x-input validation="required|email" />
<x-input validation="nullable|alpha_dash|max:120" />
<x-input validation="required_if:type,company|min:3" />
```

## Available rules

The rule string accepts the same rules you'd write server-side. See
[client-side validation rules](/frontend/plugins/redot-validator-rules) for the
full list and their parameters. The common ones:

- **`required`** / **`nullable`** — required, or optional (an empty `nullable`
  field skips its other rules).
- **`required_if`** / **`required_unless`** / **`accepted_if`** — conditional on
  another field's value, e.g. `required_if:type,company`.
- **`email`** / **`url`** — format checks.
- **`min`** / **`max`** / **`between`** / **`size`** — length, numeric value,
  array count, or file size (in KB) depending on the field.
- **`alpha`** / **`alpha_num`** / **`alpha_dash`** — character-class checks.
- **`in`** / **`not_in`** / **`enum`** / **`starts_with`** / **`ends_with`** /
  **`contains`** — membership checks.
- **`confirmed`** — matches a `{name}_confirmation` field in the same form.
- **`regex:/…/`** — test against a regex literal.

## Supporting attributes

A few sibling attributes tune how a field is validated:

- **`disable-validation`** — skip a field, or set it on a `<form>` to opt the
  whole form out (handy for logout/utility forms that submit immediately).
- **`validation-type`** — force the value type (`string`, `array`, `numeric`,
  `file`, `date`) when auto-detection guesses wrong.
- **`validation-{rule}-message`** — override the message for one rule on this
  field, e.g. `validation-required-message="…"`.
- **`validation-container`** — point a group's errors at a shared wrapper. Used
  by checkbox/radio groups, which carry the rule on the first input only.

## Examples

### Optional, but confirmed when filled

```blade
<x-input type="password" name="password" :title="__('New Password')" validation="nullable|confirmed" />
```

### Opting a form out

```blade
<x-form id="logout-form" :action="route('logout')" method="POST" disable-validation />
```

### Adding your own rule

Register a project-specific rule once on the page:

```js
RedotValidator.addRule('phone', {
    callback: ({ value }) => /^\+?\d{7,15}$/.test(value),
    message: ({ label }) => __('validation.phone', { attribute: label }),
});
```

`callback` returns `false` to fail; `message` returns the error string (resolve
it through [`__()`](/frontend/translations) so it's localized). See
[validation rules](/frontend/plugins/redot-validator-rules) for the arguments
passed to each.

### Validating in your own script

```js
if (RedotValidator.validate($form)) {
    // every field passes
}

const errors = RedotValidator.errors($form);
// { email: { email: 'The email must be a valid email address.' } }
```

## Gotchas

- **`confirmed`** looks for `{name}_confirmation` inside the field's `<form>` —
  name the partner field accordingly.
- **`nullable` first.** An empty `nullable` field passes regardless of later
  rules; a filled one runs them.
- **File-size rules are in KB** (`max:2048` = 2 MB), matching Laravel.
- Hidden fields (via [Redot Visibility](/frontend/plugins/redot-visibility)) are
  excluded from validation automatically while hidden.

## Related

- [Validation rules](/frontend/plugins/redot-validator-rules) — every built-in rule.
- [JS Translations](/frontend/translations) — `__()`, used for messages.
- [Redot Visibility](/frontend/plugins/redot-visibility) — skips validation on hidden fields.
- [Input](/components/input) — the standard field that exposes `validation`.
