# Rich Editor

`<x-rich-editor>` renders a WYSIWYG rich text editor by wrapping the [Textarea component](/components/textarea) and initializing it with [TinyMCE](/frontend/inits/tinymce). It composes a [Label](/components/label) and an optional [Hint](/components/hint) around the editable area, and lazily pushes the TinyMCE vendor script onto the page.

## What it is

A class-based Blade component backed by `App\View\Components\RichEditor` (view `components.rich-editor`). It does not render an editor surface itself — it delegates to `<x-textarea init="tinymce">`, and the `init="tinymce"` attribute is what the frontend asset system uses to boot a TinyMCE instance against the underlying `<textarea>`.

## Props

These come from the constructor of `App\View\Components\RichEditor`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto-generated | The element id, forwarded to the label's `for` and the textarea's `id`. If omitted, defaults to `uniqid('rich-editor-')`. |
| `title` | `?string` | `null` | Label text. When set, renders an `<x-label>` above the editor. When empty, no label is rendered. |
| `hint` | `?string` | `null` | Helper text. When set, renders an `<x-hint class="mt-1">` below the editor. |
| `value` | `?string` | `null` | Initial HTML content of the editor. Passed through to the textarea's `value`. |

### Pass-through attributes

Any additional attributes land on the component's attribute bag, but only `name` and `validation` are forwarded to the inner `<x-textarea>` (via `$attributes->only(['name', 'validation'])`). Other attributes are ignored by the rendered markup.

- `name` — the form field name submitted with the parent form.
- `validation` — validation rules string. If it contains the substring `required`, the `required` flag is computed and passed to the label so it shows the required indicator. This is derived in `render()`:

```php
'required' => str_contains($this->attributes->get('validation') ?: '', 'required'),
```

## Slots

This component has no usable content slot. The inner `<x-textarea>` is rendered self-closing with an explicit `:value`, so any default slot passed to `<x-rich-editor>` is not output. Use the `value` prop to seed content.

## Livewire / wire:model

There is no built-in `wire:model` handling in the markup. Because only `name` and `validation` are forwarded to the textarea, a `wire:model` attribute placed on `<x-rich-editor>` would not reach the underlying `<textarea>`. The component is intended for standard form submission via its `name` attribute.

## Script loading

The TinyMCE vendor bundle is pushed once (via `@pushOnce('plugins-scripts', 'tinymce-scripts')`) using the hashed asset helper, so it is included a single time per page regardless of how many editors are rendered:

```blade
<script src="{{ hashed_asset('/vendor/tinymce/tinymce.min.js') }}"></script>
```

The actual editor boot is driven by the `init="tinymce"` attribute on the textarea — see [TinyMCE init](/frontend/inits/tinymce).

## Usage

Real example from the memos form (`resources/views/dashboard/memos/partials/form.blade.php`):

```blade
<div class="mb-3">
    <x-rich-editor name="content" :title="__('Content')" :value="old('content', $entry?->content)" />
</div>
```

With a hint and a required rule (which makes the label show the required indicator):

```blade
<x-rich-editor
    name="body"
    :title="__('Body')"
    :hint="__('Supports rich formatting.')"
    validation="required"
/>
```

## Related

- [Textarea component](/components/textarea) — the editable surface this wraps.
- [TinyMCE init](/frontend/inits/tinymce) — the JS that upgrades the textarea into a rich editor.
- [Label](/components/label) and [Hint](/components/hint) — the surrounding chrome.
