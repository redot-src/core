# Hint

`<x-hint>` is a tiny anonymous Blade component that renders helper text below a form control. It outputs a styled `<small>` element carrying the `form-hint` class.

## What it is

The component has no PHP class and no `@props` — it is purely a presentational wrapper. The entire source is:

```blade
<small {{ $attributes->merge(['class' => 'form-hint']) }}>
    {{ $slot }}
</small>
```

The text you pass goes into the default slot, and any HTML attributes you set are merged onto the `<small>` element.

## API

- **Tag:** `<x-hint>...</x-hint>`
- **Props:** none (no class, no `@props`).
- **Slot:** the default slot holds the hint text/markup that is rendered inside the `<small>`.
- **Attributes:** all attributes are forwarded via `$attributes->merge(...)`. The `class` attribute is merged with the built-in `form-hint` class (your classes are appended to it), so `<x-hint class="mt-1">` produces `class="form-hint mt-1"`. Other attributes (e.g. `id`, `data-*`) pass through unchanged.

There is no `wire:model` or Livewire behavior and no JS init bound to this component — it is static markup only.

## Usage

In practice `<x-hint>` is not usually placed directly in views; it is rendered internally by the form field components whenever they receive a `hint` value. For example, `input.blade.php` renders it conditionally:

```blade
@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif
```

So you typically get a hint by passing the `hint` prop to a field component rather than using `<x-hint>` directly:

```blade
<x-input name="email" :title="__('Email')" hint="We will never share your address." />
```

The same <span v-pre>`<x-hint class="mt-1">{{ $hint }}</x-hint>`</span> pattern is used by every form field component, including:
[input](/components/input), [textarea](/components/textarea), [select](/components/select),
[toggle](/components/toggle), [checkboxes](/components/checkboxes), [radios](/components/radios),
[radios-colored](/components/radios-colored), [date-picker](/components/date-picker),
[color-picker](/components/color-picker), [icon-picker](/components/icon-picker),
[rating](/components/rating), [rich-editor](/components/rich-editor), [query-builder](/components/query-builder),
[repeater](/components/repeater), and [uploader](/components/uploader).

## Direct use

You can also drop it anywhere you want standalone helper text styled like a field hint:

```blade
<x-hint class="mt-1">Maximum file size is 5 MB.</x-hint>
```

## Gotchas

- The `form-hint` class is always present; passing `class` adds to it rather than replacing it.
- Content is rendered via the slot (<span v-pre>`{{ $slot }}`</span>), so any escaping/HTML handling is up to what you put inside.
- There is no `hint` prop on this component itself — the `hint` prop lives on the field components that wrap it.
