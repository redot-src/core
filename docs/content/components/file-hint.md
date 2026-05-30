# File Hint

`<x-file-hint>` shows a small "view current file" link beneath a file field,
used to surface an already-uploaded file when editing a record. When the file
URL is empty it renders nothing, so it's safe to drop into create and edit forms
without guarding it.

## Usage

```blade
<x-file-hint :file="$model->document_url" />
```

By default the link opens the file in a Fancybox lightbox.

## Options

- **`file`** — URL of the current file. When empty, nothing is rendered.
- **`fancybox`** — open the file in a lightbox (on by default). Pass
  `:fancybox="false"` to make it a plain navigation link.

Any other attribute (`target`, `class`, `id`, …) is forwarded to the link.

## Examples

### Show the current file when editing

```blade
<x-file-hint :file="$model->document_url" />
```

### Open in a new tab instead of a lightbox

```blade
<x-file-hint :file="$model->attachment" :fancybox="false" target="_blank" />
```

## Related

- [Uploader](/components/uploader) — selecting and uploading files.
- [Hint](/components/hint) — general helper text under a field.
