# RedotUploader

The engine behind the [`<x-uploader>` component](/components/uploader): a drag-and-drop file widget that uploads files over AJAX, lets the user reorder/rename/remove them, and serializes the result back into the field on submit. Use the component — this plugin is wired up for you by the [`uploader` init](/frontend/inits/uploader).

## Usage

```blade
<x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
```

Files upload as soon as they are dropped or picked, and the field is populated with the uploaded files when the form is submitted.

## Options

Set these as `uploader-` attributes on the component tag (the component exposes the common ones as props — see [Uploader component](/components/uploader)):

- **`uploader-accept`** — accept filter: extensions (`.pdf`), MIME categories (`image/*`), or exact types. `*` accepts anything.
- **`uploader-multiple`** — allow more than one file (default on). Set `false` for single-file.
- **`uploader-auto-upload`** — upload on selection (default on). Set `false` to require the per-item upload button.
- **`uploader-max-size`** — max file size in bytes.
- **`uploader-sortable`** — drag-to-reorder (default on).
- **`uploader-confirmable`** — confirm before removing a file (default on).
- **`uploader-return-type`** — what gets stored: `object` (full file descriptors, default) or `url` (just URLs).

```blade
{{-- Single-file, manual upload --}}
<x-uploader name="logo" :title="__('Logo')" accept="image/*" uploader-multiple="false" uploader-auto-upload="false" />

{{-- Store plain URLs --}}
<x-uploader name="banner" accept="image/*" uploader-return-type="url" />
```

## Reacting to uploads

The plugin fires jQuery events on the field — `uploader:file:uploaded`, `uploader:file:error`, `uploader:file:removed`, `uploader:reordered`:

```js
$('#attachments').on('uploader:file:uploaded', (e, { file }) => console.log(file.url));
```

Reach the instance later with `$('#attachments').data('uploader')`.

## Gotchas

- Files are written into the field only on form submit; reading the field value mid-edit is stale.
- `accept` only filters what the user can pick — real validation happens server-side.

## Related

- [Uploader component](/components/uploader) — the `<x-uploader>` this powers.
- [Uploader init](/frontend/inits/uploader) — the `init="uploader"` that triggers it.
- [Asset & Init System](/frontend/asset-system) — how the script is loaded and wired.
