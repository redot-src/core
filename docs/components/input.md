# Input

`<x-input>` is the standard text field used across every form in the dashboard.
It renders a styled input with an optional label, hint, input-group add-ons, a
floating label, and — for passwords — a built-in show/hide toggle.

## Usage

```blade
<x-input name="title" :title="__('Title')" :value="old('title', $post?->title)" validation="required" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). Any other HTML attribute you
pass (`placeholder`, `disabled`, `readonly`, `wire:model`, …) falls through to the
underlying `<input>`.

## Options

- **`title`** — label text shown above the field.
- **`hint`** — small helper text shown beneath the field.
- **`type`** — the input type (`text` by default). Use `password` to get the
  show/hide toggle, `email`, `file`, etc.
- **`prepend`** / **`append`** — text add-ons shown before/after the field in an
  input group (e.g. a URL prefix or unit).
- **`floating`** — float the label inside the field instead of above it.
- **`flat`** — render input-group add-ons flush against the field.

## Examples

### Email field, preserving old input

```blade
<x-input type="email" name="email" :title="__('Email address')" :value="old('email')" validation="required|email" />
```

### Password field

The show/hide toggle is added automatically:

```blade
<x-input type="password" name="password" :title="__('Password')" validation="required" />
```

### Prefix add-on

```blade
<x-input name="slug" :title="__('Slug')" prepend="https://example.com/" />
```

### Disabled, read-only display

```blade
<x-input :title="__('Slug')" :value="$post->slug" disabled />
```

### File input

```blade
<x-input type="file" name="avatar" :title="__('Avatar')" />
```

## Related

- [Label](/components/label) — renders the `title` and required marker.
- [Hint](/components/hint) — renders the `hint` text.
- [Components overview](/components/overview) — shared form-field conventions.
