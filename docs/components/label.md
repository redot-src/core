# Label

`<x-label>` renders a Bootstrap/Tabler `form-label` element. It is the small building-block component that every form-field component in the dashboard uses to render its caption, but you can also use it directly.

## What it is

An anonymous Blade component (no PHP class) located at `resources/components/label.blade.php`. It outputs a single `<label>` element with the `form-label` class, an `aria-label`, a `for` attribute, and an optional `required` styling class.

The full source:

```blade
@props([
    'title' => null,
    'for' => null,
    'required' => false,
])

<label for="{{ $for }}" aria-label="{{ $title }}"
    {{ $attributes->class(['form-label', 'required' => $required]) }}>
    {!! $title !!}
</label>
```

## Props

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `title` | string\|null | `null` | The label text. Rendered with `{!! !!}` (unescaped), so HTML in `title` is allowed, and it is also mirrored into the `aria-label` attribute. |
| `for` | string\|null | `null` | The `id` of the form control this label is bound to. Emitted as the standard `for="..."` attribute. |
| `required` | bool | `false` | When `true`, adds the `required` CSS class to the label, which renders the visual required indicator (asterisk). It does not add any HTML validation attribute. |

### Notes and gotchas

- The component has no class — props come from `@props`. There is no constructor to document.
- `title` is output **unescaped** (`{!! $title !!}`), enabling rich markup in the caption. Pass only trusted strings.
- Extra attributes are merged onto the `<label>` via `$attributes->class(...)`, so additional classes (e.g. `class="mb-0"`) merge with `form-label` rather than replacing it.
- This component renders presentation only. It does not bind to a control via JS and has no `wire:model` behavior of its own — wiring is the responsibility of the input it labels.

## Usage

Direct use:

```blade
<x-label title="Email address" for="email" :required="true" />
```

In practice, `<x-label>` is consumed internally by the form-field components, which forward their own `$title`, `$id`, and `$required` props to it. From `resources/components/input.blade.php`:

```blade
@if ($title && $floating === false)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif
```

The same pattern appears in `select`, `textarea`, `toggle`, `radios`, `checkboxes`, `date-picker`, `color-picker`, `icon-picker`, `rich-editor`, `query-builder`, `repeater`, `rating`, `uploader`, and `attachments`. The `captcha` component always passes `:required="true"`, and `attachments` omits `required` entirely (defaulting to `false`):

```blade
<x-label :title="$title" :for="$id" :required="true" />   {{-- captcha --}}
<x-label :title="$title" :for="$id" />                    {{-- attachments --}}
```

## Related

- [Input component](/components/input)
- [Select component](/components/select)
- [Uploader component](/components/uploader)
