# Uploader Initializer

The uploader initializer is the bridge between the `<x-uploader>` Blade component and the `RedotUploader` plugin. It is registered under the `uploader` initiator key and is auto-invoked by the generic `init()` runner whenever an element carries `init="uploader"`. It reads `uploader-`-prefixed attributes off the element, merges them with defaults, and constructs a `RedotUploader` instance bound to the element.

## What it is

The script lives at `public/assets/inits/uploader.js` in the consumer app. It exports a single arrow function `(selector, options = {})` that the init runner calls with the matched DOM element and any inline options. It does not register a global itself; instead it is loaded into `window.__inits.uploader` by the asset/init system, which is how the `init` attribute on a component resolves to this function.

It wraps the `RedotUploader` plugin (`public/assets/plugins/redot-uploader.js`), which is the vendor-style library that actually renders the file list, handles drag-and-drop, uploads via AJAX, and serializes the result back into the hidden input. See the [Uploader component / RedotUploader plugin](/components/uploader) page for the full plugin API.

## How it is triggered

The `<x-uploader>` component (class `App\View\Components\Uploader`) renders a hidden input and merges these attributes onto it:

- `init="uploader"` — tells the init runner to call this initializer.
- `uploader-accept` — the accept filter, mirrored from the `accept` prop.
- `uploader-config` — an encrypted JSON payload containing `accept`, `directory`, `locale`, `serverValidation`, `optimize`, and `thumbnail`.

The generic `init()` runner in `functions.js` finds `[init]:not([initialized])`, splits the `init` attribute on spaces, and for each token calls `window.__inits[token](element, options)`. The `options` argument is only populated if the element has an attribute literally named after the token (e.g. `uploader="..."`), so in normal use it arrives as `{}` and all configuration flows through the `uploader-` prefixed attributes instead.

## Default options

The initializer defines these defaults before merging:

```js
const defaultOptions = {
    sortable: true,
    multiple: true,
    accept: '*',
    autoUpload: true,
    maxSize: 10 * 1024 * 1024, // 10 MB
    confirmable: true,
    returnType: 'object',
};
```

These mirror a subset of `RedotUploader.options`. Any option the plugin supports but the initializer does not list (for example `endpoint`, `verboseErrors`, `config`, or the `attributes` map) falls back to the plugin's own defaults.

## Option resolution order

```js
const selectorOptions = getOptionsFromSelector(selector, 'uploader-');
options = _.merge(defaultOptions, selectorOptions, options);

const uploader = new RedotUploader(selector, options);

// Set the instance on the input element.
$(selector).data('uploader', uploader);
```

Precedence (lowest to highest):

1. `defaultOptions` — the static defaults above.
2. `selectorOptions` — every `uploader-`-prefixed attribute on the element, with the prefix stripped and the name camel-cased by `getOptionsFromSelector`. So `uploader-accept` becomes `accept`, `uploader-config` becomes `config`, and `uploader-max-size` would become `maxSize`. Values are coerced from strings to primitives (booleans, numbers).
3. `options` — the inline options passed by the init runner (normally empty).

`_.merge` is a deep merge, so object-valued options (such as a `sortable` or `confirmable` object) are merged rather than replaced.

After construction the instance is stored on the element via `$(selector).data('uploader', uploader)`, so other scripts can retrieve it with `$('#my-id').data('uploader')`.

## Locale and RTL handling

The initializer itself does no locale or RTL logic. Locale is propagated through the encrypted `uploader-config` attribute that the component builds server-side:

```php
$data = [
    'accept' => $this->accept,
    'directory' => $this->directory,
    'locale' => app()->getLocale(),
    'serverValidation' => $this->serverValidation,
    'optimize' => $this->optimize,
    'thumbnail' => $this->thumbnail,
];

return encrypt(json_encode($data, ...));
```

Because the payload is encrypted, the initializer forwards `config` to the plugin as an opaque string (via the `uploader-config` to `config` mapping) and the server decrypts it on upload. The current locale is therefore captured at render time and sent with each upload request; there is no client-side locale switching in this layer.

## Usage

The component is used directly in Blade; you do not call the initializer by hand. Real usage from the memos form (`resources/views/dashboard/memos/partials/form.blade.php`):

```blade
<div class="mb-3">
    <x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
</div>
```

The component's constructor props (from `App\View\Components\Uploader`) are:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `id` | `?string` | auto `uniqid('uploader-')` | Hidden input id. |
| `title` | `?string` | `null` | Renders an `<x-label>` when set. |
| `hint` | `?string` | `null` | Renders an `<x-hint>` when set. |
| `value` | `array\|string\|Collection\|null` | `null` | Initial files; arrays/collections are JSON-encoded. |
| `directory` | `string` | `'general'` | Upload target directory (sent in config). |
| `accept` | `string` | `'*'` | Mirrored to `uploader-accept` and into config. |
| `serverValidation` | `string` | `''` | Server-side validation rules (sent in config). |
| `optimize` | `bool` | `true` | Image optimization flag (sent in config). |
| `thumbnail` | `bool` | `true` | Thumbnail generation flag (sent in config). |

To override an initializer option that the component does not expose, add a raw `uploader-` attribute on the component tag, which `getOptionsFromSelector` will pick up:

```blade
{{-- Single-file, manual upload uploader --}}
<x-uploader name="logo" :title="__('Logo')" accept="image/*" uploader-multiple="false" uploader-auto-upload="false" />
```

```blade
{{-- Store plain URLs instead of file objects --}}
<x-uploader name="banner" accept="image/*" uploader-return-type="url" />
```

## Gotchas

- The initializer only lists a subset of options. Anything else (`endpoint`, `verboseErrors`, `config`, the `attributes` selector map) is left to the plugin defaults — set them via `uploader-` attributes if you need to change them.
- `getOptionsFromSelector` camel-cases attribute names, so use kebab-case in markup: `uploader-max-size` to set `maxSize`, `uploader-auto-upload` to set `autoUpload`.
- Inline option values are parsed from strings to primitives, so `uploader-multiple="false"` becomes the boolean `false`, not the string `"false"`.
- The script returns the instance only via `$(selector).data('uploader', ...)`; there is no return value to the init runner.

## Related

- [Uploader component / RedotUploader plugin](/components/uploader) — the wrapped plugin and the `<x-uploader>` component.
