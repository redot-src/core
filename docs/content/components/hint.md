# Hint

`<x-hint>` renders small helper text styled like a form-field hint. Most of the
time you get a hint by passing the `hint` attribute to a field component, but you
can also drop `<x-hint>` anywhere you want standalone helper text.

## Usage

```blade
<x-hint class="mt-1">Maximum file size is 5 MB.</x-hint>
```

The text goes in the default slot. Any `class` you pass is added to the built-in
hint styling rather than replacing it.

## Options

This component has no options. Pass the text as the slot content; any HTML
attributes (`class`, `id`, `data-*`, …) are forwarded to the element.

## Examples

### As a field's hint

Usually you don't place `<x-hint>` yourself — pass `hint` to a field component
and it renders one for you:

```blade
<x-input name="email" :title="__('Email')" hint="We will never share your address." />
```

### Standalone helper text

```blade
<x-hint class="mt-1">Maximum file size is 5 MB.</x-hint>
```

## Related

- [Label](/components/label) — the field caption building block.
- [Components overview](/components/overview) — the `hint` shared form-field attribute.
