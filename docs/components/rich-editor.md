# Rich Editor

`<x-rich-editor>` is a WYSIWYG rich-text field. Use it wherever you need
formatted HTML content (post bodies, page content, descriptions) instead of plain text.

## Usage

```blade
<x-rich-editor name="body" :title="__('Body')" :value="old('body', $post?->body)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `validation`). Seed the editor's initial HTML with
`value`. The editor initializes itself through the
[asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the editor.
- **`hint`** — helper text shown below the editor.
- **`value`** — initial HTML content.
- **`id`** — element id; auto-generated when omitted.

## Examples

### Content field, preserving old input

```blade
<x-rich-editor name="body" :title="__('Body')" :value="old('body', $post?->body)" />
```

### Required body with a hint

```blade
<x-rich-editor
    name="body"
    :title="__('Body')"
    :hint="__('Supports rich formatting.')"
    validation="required"
/>
```

## Related

- [Textarea](/components/textarea) — the plain-text field this builds on.
- [TinyMCE init](/frontend/inits/tinymce) — how the editor initializes.
- [Components overview](/components/overview) — shared form-field conventions.
