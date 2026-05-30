# Textarea

`<x-textarea>` is the standard multi-line text field, with an optional label and
hint. It can auto-grow its height as the user types.

## Usage

```blade
<x-textarea name="bio" :title="__('Biography')" :value="old('bio', $entry?->bio)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). Any other HTML attribute you
pass (`placeholder`, `rows`, `wire:model`, …) falls through to the underlying
`<textarea>`.

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — initial content.
- **`autosize`** — grow the field's height automatically as content is added.
- **`id`** — element id; auto-generated when omitted.

## Examples

### Custom-code setting

```blade
<x-textarea name="head_code" :title="__('Head code')" :value="setting('head_code')" />
```

### Auto-growing, required field

```blade
<x-textarea
    name="bio"
    :title="__('Biography')"
    :hint="__('Tell us about yourself')"
    :autosize="true"
    validation="required|max:500"
    rows="4"
/>
```

## Related

- [Input](/components/input) — single-line text field.
- [Rich Editor](/components/rich-editor) — a WYSIWYG editor built on this field.
- [Components overview](/components/overview) — shared form-field conventions.
