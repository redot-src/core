# Uploader

`<x-uploader>` renders a drag-and-drop file upload field backed by the bundled `redot-uploader.js` plugin (`RedotUploader`). It stores the uploaded file references in a hidden input so the value is submitted with a normal form post.

## What it is

The component is backed by `App\View\Components\Uploader` (extends the dashboard's base `App\View\Components\Component`). Its Blade view (`resources/components/uploader.blade.php`) renders an optional label, a hidden `<input>` that holds the value, an empty-state placeholder, and a list container that the JS plugin populates. On render it pushes the uploader plugin script once into the `plugins-scripts` stack and tags the input with `init="uploader"` so the global init runner wires up `RedotUploader`.

## Props

These are the constructor arguments of `App\View\Components\Uploader`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` → `uniqid('uploader-')` | DOM id of the hidden input. Auto-generated when omitted. |
| `title` | `?string` | `null` | Label text. When set, renders `<x-label :title :for :required />`. |
| `hint` | `?string` | `null` | Helper text rendered below the field via `<x-hint>`. |
| `value` | `array\|string\|Collection\|null` | `null` | Initial value. Arrays/Collections are JSON-encoded into the hidden input. |
| `directory` | `string` | `'general'` | Server-side storage directory; passed through in the encrypted config. |
| `accept` | `string` | `'*'` | Accepted file types. Set both on `uploader-accept` and inside the config. |
| `serverValidation` | `string` | `''` | Server-side validation rules forwarded (encrypted) to the upload endpoint. |
| `optimize` | `bool` | `true` | Whether the server should optimize uploaded images. |
| `thumbnail` | `bool` | `true` | Whether to generate/show thumbnails. |

### Derived attributes

In `render()` the component merges these onto the hidden input's attribute bag:

- `init="uploader"` — triggers the `uploader` JS init.
- <span v-pre>`uploader-accept="{{ $accept }}"`</span>
- `uploader-config="..."` — an **encrypted** JSON blob (`encrypt(json_encode(...))`) containing `accept`, `directory`, `locale`, `serverValidation`, `optimize`, and `thumbnail`. Because it is encrypted, the client cannot tamper with these server-trusted settings.

The view also computes `$required` from the attribute bag: it is `true` when the `validation` attribute contains `required` (e.g. `validation="required"`), which is forwarded to the label.

Any extra attributes you pass (such as `name`, `validation`, etc.) flow through <span v-pre>`{{ $attributes }}`</span> onto the hidden input.

## Slots

This component exposes no named or default slots; all content comes from props.

## JS behavior

The init is defined in `public/assets/inits/uploader.js`. It reads `uploader-*` attributes off the element (via `getOptionsFromSelector(selector, 'uploader-')`), merges them over the defaults below, instantiates `new RedotUploader(selector, options)`, and stores the instance with `$(selector).data('uploader', uploader)`.

Default options:

```js
{
    sortable: true,
    multiple: true,
    accept: '*',
    autoUpload: true,
    maxSize: 10 * 1024 * 1024, // 10 MB
    confirmable: true,
    returnType: 'object',
}
```

Note that the Blade component only forwards `uploader-accept` and `uploader-config` as attributes, so option overrides like `multiple` or `maxSize` are not exposed as component props — they take their defaults unless you add the matching `uploader-*` attribute yourself.

## Usage

Real example from the memos form (`resources/views/dashboard/memos/partials/form.blade.php`):

```blade
<div class="mb-3">
    <x-uploader name="attachments" :title="__('Attachments')" :value="old('attachments', $entry?->attachments)" directory="memos" />
</div>
```

With a hint and required validation:

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

## Gotchas

- The `uploader-config` blob is encrypted server-side, so `directory`, `serverValidation`, `optimize`, and `thumbnail` cannot be altered from the browser.
- `value` accepts an array or Collection and is JSON-encoded automatically; pass the same shape `RedotUploader` returns (`returnType: 'object'` by default).
- The plugin script is pushed once per page via `@pushOnce('plugins-scripts', 'uploader-scripts')`; rendering multiple uploaders does not duplicate the script tag.
- `required` is inferred only from a `validation` attribute containing the word `required`.

## Related

- [Label component](/components/label)
- [Hint component](/components/hint)
