# Attachments

`<x-attachments>` renders a read-only gallery of already-uploaded files. Each attachment is shown as a downloadable card with an image thumbnail (for images) or a MIME-based Font Awesome icon, plus the file name and a human-readable size. It is the display counterpart to the [Uploader component](/components/uploader).

## What it is

A class-based Blade component backed by `App\View\Components\Attachments` (view `components.attachments`). It takes an array of attachment metadata and iterates over it; it does **not** upload, select, or mutate files and binds to no Livewire model. Use it to present files attached to a record (memos, tickets, etc.).

## Props

The props are the constructor arguments of `App\View\Components\Attachments`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto-generated | The element id, forwarded to the `<x-label for>`. If `null`, `render()` assigns `uniqid('attachments-')`. |
| `attachments` | `array` | required | List of attachment items. Each item is an associative array (see below). |
| `title` | `?string` | `null` | Optional label rendered above the list via `<x-label>`. When `null`, no label is shown. |

### Attachment item shape

Each entry in the `attachments` array is read with these keys:

| Key | Required | Used for |
| --- | --- | --- |
| `url` | yes | The `href` of the download link and the `src` fallback for image previews. |
| `type` | yes | MIME type — drives image detection (`str_starts_with($type, 'image/')`) and icon selection. |
| `name` | yes | Displayed file name and image `alt`. |
| `size` | yes | Integer byte count, formatted by `formatFileSize()`. |
| `thumbnail` | no | Preferred image `src`; falls back to `url` when absent. |

## Behavior and helpers

- Each item renders as `<a href="{url}" class="uploader-item ..." download>` so clicking downloads the file. The list wrapper carries the bare `attachments-list` attribute.
- Image items (`type` starting with `image/`) render an `<img class="uploader-item-image">` using `thumbnail ?? url`. All other types render `<i class="fas {icon} fa-3x text-muted">`.
- `getFileIcon(string $mimeType): string` maps MIME types to Font Awesome classes: PDFs → `fa-file-pdf`; Word → `fa-file-word`; Excel → `fa-file-excel`; PowerPoint → `fa-file-powerpoint`; zip/rar/7z → `fa-file-archive`; `text/plain` → `fa-file-alt`; html/css/js/json → `fa-file-code`; `video/` → `fa-file-video`; `audio/` → `fa-file-audio`; everything else → `fa-file`.
- `formatFileSize(int $size): string` converts bytes to `B`/`KB`/`MB`/`GB`/`TB`, dividing by 1024 and rounding to 2 decimals (e.g. `1536` → `1.5 KB`).

## Slots

None. The component has no slot; content is generated entirely from the `attachments` array.

## Usage

Real usage from the memo detail view (`resources/views/dashboard/memos/show.blade.php`), guarded so the gallery only renders when there are attachments:

```blade
@if ($memo->attachments)
    <div class="card-footer">
        <x-attachments :attachments="$memo->attachments" />
    </div>
@endif
```

With an optional title and explicit id:

```blade
<x-attachments
    id="memo-files"
    title="Attached files"
    :attachments="$memo->attachments"
/>
```

## Gotchas

- `attachments` is type-hinted `array` with no default, so it must always be passed. Wrap the component in an `@if` (as the memos view does) when the source can be empty or null.
- The `video/` and `audio/` icon keys in `getFileIcon()` are matched by exact MIME string, not by prefix — only the literal values `video/` / `audio/` hit those arms; concrete types like `video/mp4` fall through to the default `fa-file` icon. Image detection, by contrast, uses a prefix check.
- Markup reuses the `uploader-item*` CSS classes, so it shares styling with the [Uploader component](/components/uploader).

## Related

- [Uploader component](/components/uploader) — the input-side component for selecting/uploading files.
