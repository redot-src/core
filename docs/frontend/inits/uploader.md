# Uploader Initializer

`uploader` bootstraps the [RedotUploader plugin](/frontend/plugins/redot-uploader) on a field, turning it into a drag-and-drop, AJAX file uploader. It backs the [`<x-uploader>` component](/components/uploader).

## Enable it

You don't enable this by hand — render the [`<x-uploader>` component](/components/uploader), whose hidden field carries `init="uploader"`:

```blade
<x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set these as `uploader-` attributes on the component tag (the component also exposes `accept` as a prop):

- **`uploader-accept`** — accept filter: extensions, MIME categories (`image/*`), or types.
- **`uploader-multiple`** — allow more than one file (default on). Set `false` for single-file.
- **`uploader-auto-upload`** — upload on selection (default on). Set `false` to require the per-item upload button.
- **`uploader-max-size`** — max file size in bytes.
- **`uploader-sortable`** — drag-to-reorder (default on).
- **`uploader-confirmable`** — confirm before removing a file (default on).
- **`uploader-return-type`** — what gets stored: `object` (full descriptors, default) or `url`.

```blade
{{-- Single-file, manual upload --}}
<x-uploader name="logo" :title="__('Logo')" accept="image/*" uploader-multiple="false" uploader-auto-upload="false" />

{{-- Store plain URLs --}}
<x-uploader name="banner" accept="image/*" uploader-return-type="url" />
```

The full set of behaviors, events, and the instance API lives on the plugin page.

## Related

- [RedotUploader plugin](/frontend/plugins/redot-uploader) — upload behavior, events, and the instance API.
- [Uploader component](/components/uploader) — the component that enables this for you.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
