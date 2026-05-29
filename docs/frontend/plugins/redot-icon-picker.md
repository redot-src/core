# RedotIconPicker

`RedotIconPicker` is the client-side plugin that powers the `<x-icon-picker>` component. It turns a plain text input into a Font Awesome icon picker: a live preview swatch plus a search modal backed by the public Font Awesome GraphQL API. The selected icon's class string (e.g. `fa-solid fa-house`) is written back into the input.

## What it is

The plugin is shipped as a single global class defined in `public/assets/plugins/redot-icon-picker.js` and loaded on demand by the Blade component:

```blade
@pushOnce('plugins-scripts', 'icon-picker-scripts')
    <script src="{{ hashed_asset('assets/plugins/redot-icon-picker.js') }}"></script>
@endPushOnce
```

It defines a single global, `RedotIconPicker`, and relies on globals provided by the platform layer: `$` (jQuery), `_` (lodash), the `$.confirm` modal plugin (jQuery-Confirm), and the `__()` translation helper.

## Constructor

```js
new RedotIconPicker(selector, options = {})
```

| Argument   | Type            | Description                                                          |
| ---------- | --------------- | -------------------------------------------------------------------- |
| `selector` | string\|element | The text input to bind. Resolved via `$(selector)`.                  |
| `options`  | object          | Optional overrides merged (via `_.merge`) over the defaults below.   |

On construction it resolves the surrounding DOM by walking up from the input:

- `$input` — `$(selector)`
- `$wrapper` — `this.$input.closest('[iconpicker-wrapper]')`
- `$preview` — `this.$wrapper.find('[iconpicker-preview]')`
- `$picker` — `this.$wrapper.find('[iconpicker-picker]')`

then calls `init()`.

## Options

| Option           | Default                          | Description                                                        |
| ---------------- | -------------------------------- | ------------------------------------------------------------------ |
| `endpoint`       | `'https://api.fontawesome.com'`  | Font Awesome GraphQL API base URL.                                 |
| `version`        | `'6.4.2'`                        | Font Awesome version passed to the `search` query.                 |
| `maxResults`     | `100`                            | Max icons fetched per query (`first:` in the GraphQL query).       |
| `searchDebounce` | `100`                            | Debounce delay (ms) on the modal search input.                     |
| `attributes`     | object (see below)               | Attribute names used to locate the picker's sub-elements.          |

### `attributes`

The plugin locates every sub-element by HTML attribute selector, so these names must match the markup in the Blade template:

| Key        | Default                | Selector usage                                          |
| ---------- | ---------------------- | ------------------------------------------------------- |
| `template` | `'iconpicker-template'`| Source `<template>` whose HTML fills the modal body.    |
| `modal`    | `'iconpicker-modal'`   | Wrapper rendered inside the modal content.              |
| `search`   | `'iconpicker-search'`  | Search input inside the modal.                          |
| `list`     | `'iconpicker-list'`    | Container the icon tiles are appended to.               |
| `icon`     | `'iconpicker-icon'`    | Each icon tile; its value holds the icon class string.  |
| `empty`    | `'iconpicker-empty'`   | "No icons found" message.                               |
| `loading`  | `'iconpicker-loading'` | Loading spinner shown during a fetch.                   |

## Public methods

- `init()` — binds events, then triggers a `change` on the input to render the initial preview.
- `bindEvents()` — on input `change`/`input` updates the preview; on `$picker` click opens the modal.
- `updatePreview()` — sets the preview element class to `icon icon-sm <input value>`.
- `openModal()` — opens a `$.confirm` modal seeded from the `[iconpicker-template]` HTML, with Cancel and Select buttons.
- `bindModalEvents($content)` — debounced search keyup; clicking an icon tile toggles the `selected` class (single selection).
- `searchIcons($content, term = '')` — runs the Font Awesome GraphQL `search` query, shows/hides the loading state, and populates results. Errors are logged and surface the empty state.
- `populateIcons($content, icons)` — renders each icon's first free family/style as `fa-<style> fa-<id>`; icons without free styles are skipped; toggles the empty message.
- `showError($content)` — clears tiles and shows the empty message.
- `saveSelection($content)` — writes the selected tile's class into the input and triggers `change`. No-op if nothing is selected.

## Events

The plugin does not emit custom events of its own. It drives the standard input `change` event (fired on init and on selection), which the preview handler and any external `change` listeners observe. Modal lifecycle hooks (`onOpenBefore`, button `action`) come from jQuery-Confirm.

## How it is initialized

The plugin is not instantiated directly in app code. The init registry binds it through the `icon-picker` initiator in `public/assets/inits/icon-picker.js`, invoked by the global `init` system when it encounters `init="icon-picker"`:

```js
// public/assets/inits/icon-picker.js
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

Notes:

- `getOptionsFromSelector(selector, 'iconpicker-')` reads any `iconpicker-*` HTML attributes off the input and merges them in as options (kebab keys become camelCase). For example `iconpicker-max-results="50"` would set `maxResults: 50`.
- The instance is stored on the element via `$(selector).data('iconPicker', iconPicker)`, so you can retrieve it later with `$('#my-input').data('iconPicker')`.
- The Blade input opts in via `->merge(['init' => 'icon-picker'])`, which the global `init` system picks up.

## Markup contract

The `<x-icon-picker>` component (`resources/components/icon-picker.blade.php`) renders the exact attribute hooks the plugin expects:

```blade
<template iconpicker-template>
    <input type="text" class="form-control" placeholder="{{ __('Search for an icon') }}..." iconpicker-search />
    <div iconpicker-list>
        <p iconpicker-empty>{{ __('No icons found.') }}</p>
        <p iconpicker-loading><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></p>
    </div>
</template>

<div class="row g-2 w-100" iconpicker-wrapper>
    <div class="col input-icon">
        <span class="input-icon-addon">
            <i class="icon icon-sm {{ $attributes->get('value') }}" iconpicker-preview></i>
        </span>
        <input type="text" id="{{ $id }}" autocomplete="off"
            {{ $attributes->class(['form-control'])->merge(['init' => 'icon-picker']) }} />
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-icon" tabindex="-1" iconpicker-picker>
            <i class="fa fa-search"></i>
        </button>
    </div>
</div>
```

## Usage

In practice you never touch the plugin directly — use the Blade component, which loads the script and wires the `init` attribute for you. Real usage from the memos form (`resources/views/dashboard/memos/partials/form.blade.php`):

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'far fa-note-sticky')" />
```

The component (`App\View\Components\IconPicker`) exposes `id`, `title`, and `hint`; an `id` is auto-generated (`uniqid('icon-picker-')`) when omitted, and `required` is derived from a `validation` attribute containing `required`. Pass the initial icon class via `value` (the preview renders it as `icon icon-sm <value>`).

## Gotchas

- The picker depends on the public Font Awesome API being reachable; failed fetches are logged to the console and shown as the empty state, not as an error toast.
- The GraphQL query only returns icons that have a free family/style; paid-only icons are filtered out by `populateIcons`.
- Selection is single-choice — clicking a tile clears `selected` from all others before marking the clicked one.
- The script tag is pushed once via `@pushOnce('plugins-scripts', 'icon-picker-scripts')`, so it loads only when at least one icon picker is on the page.

## Related

- [Icon Picker component](/components/icon-picker) — the Blade component this plugin powers.
- [Init system](/frontend/asset-system) — the `init="..."` registry that instantiates the plugin and supplies `getOptionsFromSelector`.
