# File Hint

`<x-file-hint>` renders a small "view current file" link beneath a form field, used to surface an already-uploaded file when editing a record. When a file URL is present it outputs a Fancybox-enabled anchor; when it is empty the component renders nothing.

## What it is

A Blade component backed by `App\View\Components\FileHint` (`app/View/Components/FileHint.php`) whose view lives at `resources/components/file-hint.blade.php`. It is typically paired with a file input to show the current value of that field on edit forms.

The whole output is conditional: if `$file` is `null` or empty, nothing is rendered.

## Props

The component class constructor defines the props:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `file` | `?string` | `null` | URL of the current file. When falsy the component renders nothing. |
| `fancybox` | `bool` | `true` | When `true`, the link gets a `data-fancybox` attribute so clicking it opens the file in a Fancybox lightbox instead of navigating away. |

### Rendered markup and attributes

`render()` manipulates the attribute bag before producing the view:

- The link always receives the `text-decoration-none` class.
- When `fancybox` is `true`, `data-fancybox=""` is merged onto the link.
- Any extra HTML attributes you pass on the tag are forwarded onto the `<a>` via <span v-pre>`{{ $attributes }}`</span>.

The view itself:

```blade
@if ($file)
    <a href="{{ $file }}" {{ $attributes }}>
        <small class="form-hint mt-1">{{ __('Click here to view the current file.') }}</small>
    </a>
@endif
```

The link text "Click here to view the current file." is localized through `__()`. There are no slots; the label is fixed.

### Base class

`FileHint` extends `App\View\Components\Component`, a thin abstract base whose `attributes()` helper lets the component rewrite its `ComponentAttributeBag` (used here to add the class and merge `data-fancybox`).

## Fancybox behavior

The `data-fancybox` attribute is consumed by the Fancybox vendor library bundled with the dashboard (`public/vendor/fancybox/fancybox.min.js`, loaded in `resources/layouts/scaffold.blade.php`). Global Fancybox defaults are configured in `public/assets/js/app.js`, which adds a `fancybox-type-<type>` class to the container on load. The component does not register its own JS; it only emits the `data-fancybox` hook that the global Fancybox setup binds to.

To make the link a plain navigation link instead of a lightbox trigger, pass `:fancybox="false"`.

## Usage

```blade
{{-- Show the existing file when editing, opening it in a Fancybox lightbox --}}
<x-file-hint :file="$model->document_url" />
```

```blade
{{-- Disable the lightbox so the browser navigates to the file directly --}}
<x-file-hint :file="$model->attachment" :fancybox="false" target="_blank" />
```

Any additional attributes (e.g. `target`, `class`, `id`) are merged onto the underlying `<a>` element.

## Gotchas

- Renders nothing when `file` is empty, so it is safe to drop into create/edit forms without guarding it yourself.
- The hint text is not customizable through a slot or prop; it is the localized string "Click here to view the current file."
- `data-fancybox` only does something when the Fancybox vendor script is loaded on the page (it is in the default `scaffold` layout). On pages without it, the link still works as a normal anchor.

## Related

- [Uploader component](/components/uploader)
