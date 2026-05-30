# Label

`<x-label>` renders a form field's caption — the styled label text with an
optional required marker. Every form-field component uses it internally, but you
can also use it directly.

## Usage

```blade
<x-label title="Email address" for="email" :required="true" />
```

## Options

- **`title`** — the label text. HTML is allowed, so pass only trusted strings.
- **`for`** — the id of the form control the label is bound to.
- **`required`** — show the required marker (an asterisk). This is visual only;
  it doesn't add any validation.

Any extra class you pass is added to the built-in label styling rather than
replacing it.

## Examples

### Standalone label

```blade
<x-label title="Email address" for="email" :required="true" />
```

Most of the time you don't place `<x-label>` yourself — field components render
one for you from their `title` attribute.

## Related

- [Hint](/components/hint) — helper text under a field.
- [Components overview](/components/overview) — the `title` shared form-field attribute.
