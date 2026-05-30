# Uploader

`<x-uploader>` is a drag-and-drop file upload field. Files upload as they're
dropped, and their references are stored in a hidden input so the value submits
with a normal form post. It's the input counterpart to
[Attachments](/components/attachments).

## Usage

```blade
<x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`), and initializes itself through
the [asset & init system](/frontend/asset-system). Seed `value` with the same
shape the uploader stored previously.

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — initial uploaded files.
- **`directory`** — server-side storage directory (`general` by default).
- **`accept`** — accepted file types (e.g. `image/*,application/pdf`).
- **`server-validation`** — server-side validation rules applied to each upload.
- **`optimize`** — optimize uploaded images on the server (on by default).
- **`thumbnail`** — generate and show thumbnails (on by default).
- **`id`** — element id; auto-generated when omitted.

## Examples

### Files attached to a record

```blade
<x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
```

### Restricted types with a hint

```blade
<x-uploader
    name="documents"
    :title="__('Documents')"
    :hint="__('PDF or images, up to 10 MB each')"
    accept="image/*,application/pdf"
    directory="documents"
    validation="required"
/>
```

## Related

- [Attachments](/components/attachments) — the read-only display of uploaded files.
- [Uploader init](/frontend/inits/uploader) — how the field initializes.
- [Components overview](/components/overview) — shared form-field conventions.
