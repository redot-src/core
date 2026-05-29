# Repeater Initializer

The repeater initializer is a small init script that bootstraps the [`RedotRepeater`](/frontend/plugins/redot-repeater) plugin on a hidden input rendered by the `<x-repeater>` Blade component. It reads per-element options from `repeater-*` attributes, merges them with sensible defaults, and stores the resulting instance on the element via jQuery `data`.

## What it is

The script lives at `public/assets/inits/repeater.js` and registers an initiator function. It does not define a global itself; it is wired into the dashboard's init system (`window.__inits`) and triggered for any element carrying `init="repeater"`.

The full source:

```js
/**
 * Initialize the repeater input.
 *
 * @param {string} selector
 * @param {object} options
 * @see RedotRepeater
 */
return (selector, options = {}) => {
    const defaultOptions = {
        sortable: true,
        scrollable: true,
        confirmable: true,
    };

    const selectorOptions = getOptionsFromSelector(selector, 'repeater-');
    options = _.merge(defaultOptions, selectorOptions, options);

    const repeater = new RedotRepeater(selector, options);

    // Set the instance on the input element.
    $(selector).data('repeater', repeater);
};
```

It wraps the `RedotRepeater` class shipped in `assets/plugins/redot-repeater.js`, which itself uses `Sortable` for drag ordering and jQuery Confirm (via the shared `warnBeforeAction` helper) for the confirmation dialogs.

## How it is triggered

The `<x-repeater>` component renders a hidden input with `init="repeater"`:

```blade
<input type="hidden" id="{{ $id }}" value="{{ $value }}"
    {{ $attributes->merge(['init' => 'repeater']) }}>
```

The dashboard's `init()` function (in `assets/js/functions.js`) scans for `[init]:not([initialized])` elements, looks up each initiator name in `window.__inits`, and calls it with the element. Because the `init` attribute does not carry an inline JSON value here, the `options` argument passed to this initiator is `{}` — all options come from the element's `repeater-*` attributes instead.

## Options

Three defaults are set directly in the script, then merged (via `_.merge`, in increasing priority) with attribute-derived options and the inline `options` argument:

| Option | Default | Meaning |
| --- | --- | --- |
| `sortable` | `true` | Enable drag-to-reorder. Accepts `true`/`false` or a `Sortable` options object. |
| `scrollable` | `true` | Scroll to a newly inserted item. |
| `confirmable` | `true` | Show a confirmation dialog before removing or clearing items. Accepts `true`/`false` or a jQuery Confirm options object. |

Additional `RedotRepeater` options (`initialItems`, `itemTag`, `animations`, `actions`, `attributes`) keep their plugin defaults unless overridden — see [RedotRepeater](/frontend/plugins/redot-repeater) for the full option set.

### Attribute-driven options (`repeater-*`)

`getOptionsFromSelector(selector, 'repeater-')` collects every attribute that starts with `repeater-`, strips the prefix, converts the remainder to camelCase, runs the value through `stringToPrimitive` (so `"true"`/`"false"`/numbers/JSON become real primitives), and assigns it via `_.set` (dotted keys build nested objects). Examples:

- `repeater-sortable="false"` -> `{ sortable: false }`
- `repeater-scrollable="false"` -> `{ scrollable: false }`
- `repeater-initial-items="3"` -> `{ initialItems: 3 }`

Note that the structural marker attributes used by the component markup (`repeater-container`, `repeater-wrapper`, `repeater-list`, `repeater-template`, `repeater-empty`, `repeater-item`) live on other elements, not on the hidden input, so they are not picked up as options here.

## Locale / RTL handling

This init script does not perform any locale or direction handling itself. Localization of the confirmation dialog text and RTL layout are delegated entirely to the `RedotRepeater` plugin, which routes removal/clear confirmations through the shared `warnBeforeAction` helper (jQuery Confirm). When `confirmable` is an object, it is passed straight through as jQuery Confirm options:

```js
warnBeforeAction(
    () => this.remove(item, true),
    _.isPlainObject(this.options.confirmable) ? this.options.confirmable : {},
);
```

## Retrieving the instance

After initialization the live instance is stored on the input element, so you can reach it later:

```js
const repeater = $('#my-repeater').data('repeater');
```

## Usage

Real usage from the consumer app — the `<x-repeater>` component (`resources/components/repeater.blade.php`) is the standard trigger. The slot defines the template that is cloned per item:

```blade
<x-repeater id="links" :title="__('Links')">
    <x-input name="links[__index__][label]" :title="__('Label')" />
    <x-input name="links[__index__][url]" :title="__('URL')" />
</x-repeater>
```

The component is backed by `App\View\Components\Repeater`, whose constructor exposes the props `id`, `title`, `hint`, and `value` (array/string/Collection — JSON-encoded into the hidden input). Any extra attributes are merged onto the hidden input, so you can attach options through the `repeater-*` convention:

```blade
<x-repeater id="tags" :title="__('Tags')" repeater-sortable="false" />
```

The plugin script is loaded once by the component itself via `@pushOnce('plugins-scripts', 'repeater-scripts')`, so you do not need to include `redot-repeater.js` manually.

## Related

- [RedotRepeater plugin](/frontend/plugins/redot-repeater) — the class this initializer instantiates, and the source of the remaining options and events.
- [Sortable initializer](/frontend/inits/sortable) — the same `Sortable` library powers the repeater's drag ordering.
