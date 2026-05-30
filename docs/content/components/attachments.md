# Attachments

`<x-attachments>` shows a read-only gallery of already-uploaded files. Each file
appears as a downloadable card with an image thumbnail (for images) or a
file-type icon, plus the file name and a human-readable size. It's the display
counterpart to the [Uploader](/components/uploader).

## Usage

```blade
<x-attachments :attachments="$post->attachments" />
```

`attachments` is required, so guard the component when the source can be empty:

```blade
@if ($post->attachments)
    <x-attachments :attachments="$post->attachments" />
@endif
```

## Options

- **`attachments`** — the list of files to display. Each item is an array with
  `url` (download link), `type` (MIME type), `name`, and `size` (bytes). An
  optional `thumbnail` URL is used for the image preview when present, otherwise
  the `url` is used.
- **`title`** — optional label shown above the gallery.
- **`id`** — element id; auto-generated when omitted.

Image files render a thumbnail; everything else gets an icon chosen from its
MIME type (PDF, Word, Excel, PowerPoint, archives, text, code, video, audio).
File sizes are formatted automatically (e.g. `1.5 KB`).

## Examples

### Files attached to a record

```blade
@if ($post->attachments)
    <div class="card-footer">
        <x-attachments :attachments="$post->attachments" />
    </div>
@endif
```

### With a title

```blade
<x-attachments :title="__('Attached files')" :attachments="$post->attachments" />
```

## Related

- [Uploader](/components/uploader) — the input side for selecting and uploading files.
- [Components overview](/components/overview) — shared form-field conventions.
