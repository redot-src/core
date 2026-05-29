# Tinymce Initializer

The `tinymce` initializer configures and mounts a [TinyMCE](https://www.tiny.cloud/docs/) rich text editor on a target element. It is the JS behind the [Rich Editor component](/components/rich-editor), which renders a `<x-textarea ... init="tinymce">` that this initializer turns into a full WYSIWYG editor.

## What it is

`public/assets/inits/tinymce.js` returns an async function registered in the global `window.__inits` registry under the name `tinymce`. The init system (in `functions.js`) scans for elements carrying an `init` attribute and calls the matching initializer:

```js
// functions.js — init() loop
const options = this.hasAttribute(init) ? stringToPrimitive($(this).attr(init)) : {};
window.__inits[init](this, options); // window.__inits['tinymce'](el, options)
```

Any element with `init="tinymce"` is auto-initialized on page load (and on dynamically inserted content). The Rich Editor Blade component emits exactly that attribute.

## Signature

```js
async (selector, options = {}) => { ... }
```

- `selector` — the DOM element (or jQuery-resolvable selector) to turn into an editor. The element must have an `id`; the initializer builds TinyMCE's `selector` option as `'#' + $(selector).attr('id')`.
- `options` — optional object merged on top of the defaults. Passed via the value of the `init` / `tinymce` attribute (parsed with `stringToPrimitive`). Note the merge order is `Object.assign({}, options, defaults)`, so the built-in defaults below override any caller-supplied keys of the same name.

If `tinyMCE` is not loaded on the page, the initializer logs `TinyMCE is not loaded.` and returns without doing anything.

## Options passed to TinyMCE

The initializer assembles this configuration before calling `tinyMCE.init()`:

| Option | Value |
| --- | --- |
| `selector` | `'#' + $(selector).attr('id')` |
| `language` | `document.documentElement.lang` |
| `directionality` | `document.documentElement.dir` |
| `height` | `300` |
| `menubar` | `false` |
| `branding` | `false` |
| `skin` | `'oxide-dark'` (dark) or `'oxide'` (light) |
| `content_css` | `'dark'` or `'default'` |
| `plugins` | `'advlist autolink code directionality link lists table image'` |
| `toolbar` | `'undo redo | styles | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify outdent indent ltr rtl | bullist numlist | table image'` |
| `toolbar_mode` | `'sliding'` |
| `image_title` | `true` |
| `automatic_uploads` | `true` |
| `images_upload_handler` | custom XHR uploader (see below) |
| `file_picker_types` | `'image'` |
| `file_picker_callback` | local file picker that base64-encodes into the blob cache |

### Locale and RTL handling

Localization and text direction are read directly from the `<html>` element:

- `language` is set to `document.documentElement.lang`.
- `directionality` is set to `document.documentElement.dir`, so an RTL page (`dir="rtl"`) produces an RTL editor. The toolbar also exposes explicit `ltr rtl` buttons (from the `directionality` plugin).

### Theme handling

The editor follows the app's Bootstrap theme. It reads `data-bs-theme` from `<html>`; if that is `auto`, it resolves the OS preference via `window.matchMedia('(prefers-color-scheme: dark)')`. Dark resolves to `skin: 'oxide-dark'` / `content_css: 'dark'`, otherwise the light defaults.

When the theme changes at runtime the editor is torn down (it relies on a re-init to pick up the new skin):

```js
document.addEventListener('theme:changed', () => instance?.destroy(), { once: true });
```

### Image uploads

Two image paths are wired:

- `images_upload_handler` POSTs the dropped/pasted blob to `/tinymce/upload` with the `X-CSRF-TOKEN` header taken from `<meta name="csrf-token">`, reporting progress and expecting a JSON response `{ "location": "<url>" }`. A `403` rejects with `{ remove: true }`. This endpoint is backed by `TinymceController@store` in the dashboard app (validates `image`, stores via the `CanUploadFile` trait, returns `{ location }`).
- `file_picker_callback` opens a native file input (`accept="image/*"`), reads the chosen file as base64, and registers it in TinyMCE's `editorUpload.blobCache` so `automatic_uploads` can flush it through the upload handler.

### Instance handle

After init, the editor instance is stored on the element for later access:

```js
const [instance] = await tinyMCE.init(options);
$(selector).data('tinymce', instance);
```

## Usage

You normally never call this directly — render the [Rich Editor component](/components/rich-editor), which produces a textarea carrying `init="tinymce"` and pushes the TinyMCE vendor script:

```blade
<x-textarea :value="$value" :id="$id" :autosize="false" init="tinymce"
    {{ $attributes->only(['name', 'validation']) }} />

@pushOnce('plugins-scripts', 'tinymce-scripts')
    <script src="{{ hashed_asset('/vendor/tinymce/tinymce.min.js') }}"></script>
@endPushOnce
```

Real usages in the dashboard:

```blade
{{-- resources/views/dashboard/memos/partials/form.blade.php --}}
<x-rich-editor name="content" :title="__('Content')" :value="old('content', $entry?->content)" />
```

```blade
{{-- resources/views/dashboard/static-pages/partials/form.blade.php --}}
<x-translatable component="rich-editor" name="content"
    :value="$entry ? $entry->getTranslations('content') : old('content')" :title="__('Content')" />
```

## Gotchas

- The element **must** have an `id`; the component auto-generates one (`uniqid('rich-editor-')`) if you do not pass `:id`.
- Caller-provided `options` keys that collide with the built-in defaults are ignored, because defaults are merged last.
- The TinyMCE vendor bundle must be present at `/vendor/tinymce/tinymce.min.js`; without it the initializer no-ops with a console error. The Rich Editor component injects it via `@pushOnce`.
- On `theme:changed` the instance is destroyed (registered with `{ once: true }`), so the editor is rebuilt to apply the new skin rather than re-skinned in place.

## Related

- [Rich Editor component](/components/rich-editor) — the Blade component that emits `init="tinymce"`.
