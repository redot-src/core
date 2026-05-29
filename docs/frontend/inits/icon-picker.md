# Icon Picker Initializer

The icon picker initializer wires a text input up to the [`RedotIconPicker` plugin](/frontend/plugins/redot-icon-picker), which adds a live preview icon and a search modal backed by the FontAwesome GraphQL API. It is the initiator registered for the `icon-picker` key and is triggered automatically by the [`<x-icon-picker>` component](/components/icon-picker).

## What it is

The file at `public/assets/inits/icon-picker.js` exports an initiator function `(selector, options = {})` that is registered on `window.__inits['icon-picker']`. The global `init()` dispatcher (in `assets/js/functions.js`) scans the DOM for elements carrying an `init` attribute and calls the matching initiator for each name found:

```js
const options = this.hasAttribute(init) ? stringToPrimitive($(this).attr(init)) : {};
window.__inits[init](this, options);
```

So any element rendered with `init="icon-picker"` is auto-bound. The `<x-icon-picker>` Blade component renders exactly that:

```blade
<input type="text" id="{{ $id }}" autocomplete="off"
    {{ $attributes->class(['form-control'])->merge(['init' => 'icon-picker']) }} />
```

## What the initializer does

```js
return (selector, options = {}) => {
    const defaultOptions = {
        endpoint: 'https://api.fontawesome.com',
        version: '6.4.2',
        maxResults: 100,
        searchDebounce: 100,
    };

    const selectorOptions = getOptionsFromSelector(selector, 'iconpicker-');
    options = _.merge(defaultOptions, selectorOptions, options);

    const iconPicker = new RedotIconPicker(selector, options);

    // Set the instance on the input element.
    $(selector).data('iconPicker', iconPicker);
};
```

In order:

1. Defines the default options (see below).
2. Reads per-element options from `iconpicker-*` attributes via `getOptionsFromSelector(selector, 'iconpicker-')`.
3. Merges, with precedence `defaults < selector attributes < passed options` (last wins, via `_.merge`).
4. Instantiates `new RedotIconPicker(selector, options)`.
5. Stores the instance on the input with jQuery `.data('iconPicker', …)` so it can be retrieved later, e.g. `$('#my-input').data('iconPicker')`.

## Options

These are the defaults passed to `RedotIconPicker`. All can be overridden through `iconpicker-`-prefixed attributes on the input or through the inline `init` payload.

| Option | Default | Description |
| --- | --- | --- |
| `endpoint` | `'https://api.fontawesome.com'` | FontAwesome GraphQL API endpoint used for icon search. |
| `version` | `'6.4.2'` | FontAwesome version queried against. |
| `maxResults` | `100` | Maximum number of icons fetched per search. |
| `searchDebounce` | `100` | Debounce delay (ms) on the search input inside the modal. |

> The plugin itself also defines an `attributes` map (`iconpicker-template`, `iconpicker-modal`, `iconpicker-search`, `iconpicker-list`, `iconpicker-icon`, `iconpicker-empty`, `iconpicker-loading`) used to locate DOM hooks. See the [plugin page](/frontend/plugins/redot-icon-picker) for details.

### Reading options from selector attributes

`getOptionsFromSelector` strips the `iconpicker-` prefix, converts the remainder to camelCase, coerces values to primitives, and supports dotted keys via `_.set`. For example:

```blade
<input init="icon-picker" iconpicker-max-results="50" iconpicker-version="5.15.4" />
```

yields `{ maxResults: 50, version: '5.15.4' }`, merged over the defaults.

### Locale / RTL

This initializer performs no locale or RTL handling of its own; it passes none of those values to the plugin. Localized strings (modal title, buttons, "No icons found") are rendered server-side in the Blade template and resolved by the plugin through the global `__()` translation helper.

## Usage

### Via the component (recommended)

The component is the normal entry point. From `resources/views/dashboard/memos/partials/form.blade.php`:

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'far fa-note-sticky')" />
```

The component (`App\View\Components\IconPicker`) accepts `id`, `title`, and `hint` constructor props (all optional; `id` defaults to `uniqid('icon-picker-')`). It applies `init="icon-picker"` to the underlying input and ships the plugin script via `@pushOnce('plugins-scripts', 'icon-picker-scripts')`. See [`<x-icon-picker>`](/components/icon-picker) for the full component API.

### Plain markup

Any input with the `init` attribute is picked up on the next `init()` pass:

```blade
<div iconpicker-wrapper>
    <i iconpicker-preview class="icon icon-sm far fa-note-sticky"></i>
    <input init="icon-picker" iconpicker-max-results="200" value="far fa-note-sticky" />
    <button type="button" iconpicker-picker>Pick</button>
</div>
```

The plugin locates the preview, picker button, and wrapper relative to the input, so the surrounding `iconpicker-wrapper` / `iconpicker-preview` / `iconpicker-picker` hooks must be present (the component renders these for you).

### Retrieving the instance

```js
const picker = $('#icon-picker-input').data('iconPicker');
```

## Gotchas

- The initializer only runs for elements present when `init()` is invoked and not already marked `initialized`. Content injected later must be re-initialized by calling `init(scopeSelector)`.
- The plugin needs its DOM hooks (`iconpicker-wrapper`, `iconpicker-preview`, `iconpicker-picker`) and the `iconpicker-template` element. Use the component to guarantee they exist.
- Icon search hits the live FontAwesome API; results are limited to free family/styles and capped at `maxResults`.

## Related

- [`<x-icon-picker>` component](/components/icon-picker)
- [RedotIconPicker plugin](/frontend/plugins/redot-icon-picker)
