# RedotUploader

`RedotUploader` is the JavaScript class behind the dashboard's file uploader. It turns a hidden `<input>` and its container markup into a drag-and-drop, sortable, AJAX-uploading file widget that serializes the uploaded files back into the input on form submit. It is the engine driving the [Uploader component](/components/uploader).

## What it is

The class lives in `public/assets/plugins/redot-uploader.js` and is defined as a plain global `class RedotUploader`. It depends on:

- **jQuery** (`$`) — DOM, AJAX, events.
- **Lodash** (`_`) — `_.merge`, `_.uniqueId`, `_.escape`, `_.uniq`, `_.set`, `_.isPlainObject`.
- **Sortable** (SortableJS) — drag-to-reorder of uploaded items.
- **jQuery-Confirm** (`$.confirm`) — rename dialog and the `warnBeforeAction` confirm helper used on remove.
- **Fancybox** (`$.fancybox`) — image preview lightbox.
- **`__()`** — the dashboard i18n helper for validation/UI strings.
- **`$.error()`** — toast helper for grouped error messages.
- **`isJson()`** — helper used when parsing URL-shaped values.

It is **not** instantiated directly in app code. Instead the [uploader init](/frontend/inits/uploader) wraps it (see [Usage](#usage)).

## Constructor

```js
new RedotUploader(selector, options = {})
```

- `selector` — the hidden input. The constructor resolves the surrounding structure from it:
  - `$input` = `$(selector)`
  - `$container` = `$input.closest('[uploader-container]')`
  - `$wrapper` = `$container.find('[uploader-wrapper]')`
  - `$list` = `$wrapper.find('[uploader-list]')` (the list attribute is configurable, see `options.attributes.list`)
  - `identifier` = the input's `id`, or a generated `uploader-N` id.
- `options` — merged over the defaults below via `_.merge`.

On construction it calls `init()`, which: creates the hidden file input, binds events, loads any initial files from the input value, initializes Sortable, updates the empty state, and fires `uploader:initialized`.

## Options

All defaults are read directly from the source:

| Option | Default | Description |
| --- | --- | --- |
| `config` | `null` | Opaque config string sent with each upload as the `config` form field. The Blade component fills this with an **encrypted** JSON payload (`uploader-config`). |
| `endpoint` | `'/uploader/upload'` | POST URL files are uploaded to. |
| `sortable` | `true` | `true` to enable reordering, or a SortableJS options object (merged with internal defaults). |
| `multiple` | `true` | Allow multiple files. When `false`, adding a new file clears existing ones first. |
| `accept` | `'*'` | Accept filter. Comma-separated list of extensions (`.pdf`), MIME categories (`image/*`), or exact MIME types. `*` / `*/*` accept everything. |
| `verboseErrors` | `true` | Show toast error messages on validation/upload failure. |
| `autoUpload` | `true` | Upload files immediately on selection/drop. When `false`, the per-item upload button starts the upload. |
| `maxSize` | `10 * 1024 * 1024` (10 MB) | Max file size in bytes; oversized files are rejected with a `validation.lte.file` message. |
| `confirmable` | `true` | Confirm before removing a file. `true` uses `warnBeforeAction`; an object is passed through to it; `false` removes immediately. |
| `returnType` | `'object'` | Shape stored in the input: `'object'` stores full file descriptors as JSON; `'url'` stores just URLs (single string or JSON array). In `url` mode the rename button is hidden. |
| `attributes.list` | `'uploader-list'` | Attribute marking the list container. |
| `attributes.item` | `'uploader-item'` | Attribute marking each file item. |
| `attributes.empty` | `'uploader-empty'` | Attribute marking the empty-state element (toggled by file count). |
| `attributes.input` | `'uploader-input'` | Attribute applied to the generated hidden `<input type="file">`. |

## Bound markup and selectors

The class expects this container structure (produced by the Blade component):

- `[uploader-container]` — wraps the hidden input and wrapper.
- `[uploader-wrapper]` — the clickable drop zone. Clicking empty space (outside `[uploader-list]`) opens the file dialog. Gets a `drag-over` class during drag.
- `[uploader-list]` — items are appended here.
- `[uploader-empty]` — shown only when there are no items.

It also binds the **form `submit`** event of the closest `<form>` to call `saveToInput()`, serializing the current uploaded files into the hidden input before submission.

Each generated item carries `[uploader-item]`, a status class (`status-pending` / `status-uploading` / `status-uploaded` / `status-error`), a progress bar, and action buttons `[action="upload"]`, `[action="rename"]`, `[action="remove"]`.

## Upload flow

`uploadFile()` POSTs `multipart/form-data` to `options.endpoint` with fields `file` and `config`, an `X-CSRF-TOKEN` header from `meta[name="csrf-token"]`, and live progress updates. On success it merges `response.payload` into the file data (expecting fields like `url`, `path`, `thumbnail`). Only items with `status === 'uploaded'` are included by `getFiles()` / serialized by `saveToInput()`.

## Public methods

- `addFile(file)` — validate + render + (auto)upload a `File`.
- `uploadFile(fileData, $item)` — AJAX upload a single item.
- `removeFile($item, force = false)` — remove (with confirmation unless `force`/`confirmable: false`).
- `renameFile($item)` — open the rename dialog (preserves extension).
- `clearFiles()` — remove all items.
- `getFiles()` — array of uploaded file descriptors (`name, size, type, url, path`, plus `thumbnail` if present).
- `setFiles(files)` — render existing files (accepts a JSON string, array, or single object/URL depending on `returnType`/`multiple`).
- `saveToInput()` — serialize current files into the hidden input (called on form submit).
- `parseUrl(url)` — turn a URL into a file descriptor (used in `returnType: 'url'` mode).

## Events

All events are jQuery events triggered on the hidden input, namespaced `uploader:`, and every payload includes `{ uploader: this, ... }`:

| Event | Payload | When |
| --- | --- | --- |
| `uploader:initialized` | `{ uploader }` | After construction/init. |
| `uploader:file:added` | `{ file, item }` | A file was added to the list. |
| `uploader:file:uploaded` | `{ file, item, response }` | Upload succeeded. |
| `uploader:file:error` | `{ file, item, error }` | Upload failed. |
| `uploader:file:removed` | `{ file }` | A file was removed. |
| `uploader:cleared` | `{ uploader }` | All files cleared. |
| `uploader:reordered` | `{ uploader }` | Sortable drag finished. |

```js
$('#my-input').on('uploader:file:uploaded', (e, data) => {
    console.log(data.file.url, data.response.payload);
});
```

## Usage

The class is wired up through the `init` system rather than instantiated by hand. The init (`public/assets/inits/uploader.js`) reads `uploader-*` attributes off the element via `getOptionsFromSelector`, merges them with its defaults, constructs the uploader, and stores the instance on the element with `$(selector).data('uploader', uploader)`:

```js
return (selector, options = {}) => {
    const defaultOptions = {
        sortable: true, multiple: true, accept: '*',
        autoUpload: true, maxSize: 10 * 1024 * 1024,
        confirmable: true, returnType: 'object',
    };
    const selectorOptions = getOptionsFromSelector(selector, 'uploader-');
    options = _.merge(defaultOptions, selectorOptions, options);
    const uploader = new RedotUploader(selector, options);
    $(selector).data('uploader', uploader);
};
```

Because options come from `uploader-`-prefixed attributes, the Blade component sets `init="uploader"`, `uploader-accept`, and `uploader-config` on the hidden input. The script tag for this plugin is pushed once by the component itself:

```blade
@pushOnce('plugins-scripts', 'uploader-scripts')
    <script src="{{ hashed_asset('assets/plugins/redot-uploader.js') }}"></script>
@endPushOnce
```

### Real example from the app

The dashboard uses the component (never the raw class). From `resources/views/dashboard/memos/partials/form.blade.php`:

```blade
<x-uploader
    name="attachments"
    :title="__('Attachments')"
    :value="old('attachments', $entry?->attachments)"
    directory="memos"
/>
```

To reach the live instance imperatively:

```js
const uploader = $('#attachments').data('uploader');
uploader.clearFiles();
console.log(uploader.getFiles());
```

## Gotchas

- The class is a **global**, not a module export. It is only available on pages that render an `<x-uploader>` (the component pushes the script). Don't reference it on pages without an uploader.
- The `config` option is opaque to the JS: the Blade component encrypts a JSON payload (`accept`, `directory`, `locale`, `serverValidation`, `optimize`, `thumbnail`) server-side and the JS forwards it untouched to the upload endpoint.
- Files are only persisted into the input on **form submit** (or via `saveToInput()`); reading the input value mid-edit will be stale.
- `accept` is enforced client-side for filtering selected/dropped files; real validation happens server-side at `endpoint`.
- `removeFile` falls back to a native `confirm()` only if `warnBeforeAction` is undefined.

## Related

- [Uploader component](/components/uploader) — the `<x-uploader>` Blade component that renders the markup and pushes this script.
- [uploader init](/frontend/inits/uploader) — the initializer that constructs this class from `uploader-*` attributes.
