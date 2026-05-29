# Tomselect Initializer

The `tomselect` init script wires up enhanced `<select>` boxes using the [Tom Select](https://tom-select.js.org/) library. It registers an initiator that the platform's `init()` bootstrap calls for any element carrying `init="tomselect"`, and it adds support for tags, removable items, remote AJAX search, dependent (bound) selects, and preloading selected values.

## What it is

The file at `public/assets/inits/tomselect.js` returns a single initiator function:

```js
return (selector, options = {}) => { /* ... new TomSelect(selector, options) ... */ };
```

The platform loader registers this returned function under `window.__inits['tomselect']`. The global `init()` helper in `public/assets/js/functions.js` scans for `[init]:not([initialized])` elements and, for each space-separated token in the `init` attribute, calls `window.__inits[token](element, options)`. The options come from a matching attribute of the same name (parsed via `stringToPrimitive`), so `init="tomselect"` runs this script against that element.

It wraps the **Tom Select** vendor library (`new TomSelect(selector, options)`). The Blade component that triggers it is [`<x-select>`](#triggered-by-the-select-component), whose PHP class merges `init => 'tomselect'` onto the rendered `<select>` whenever its `tom` prop is true.

## How it is triggered

You do not normally call this initiator directly. Add the attribute to a `<select>`:

```blade
<select init="tomselect" class="form-select">
    <option value="1">One</option>
    <option value="2">Two</option>
</select>
```

In practice the `<x-select>` component renders this for you. See [the Select component section](#triggered-by-the-select-component).

## Element attributes (the API)

The initiator reads behavior from attributes on the `<select>`. Attributes are read with the helper `$select.hasAttr(name)` (presence-only flags) and `getOptionsFromSelector(selector, prefix)` (prefixed, camel-cased options).

### Flag attributes (presence only)

| Attribute | Effect |
| --- | --- |
| `tags` | Sets `create: true`, allowing users to create new options by typing. |
| `removable` | Adds the `remove_button` plugin; on init, if no `option[selected]` exists, the value is cleared. |
| `multiple` | Adds the `remove_button` plugin (standard multi-select). |
| `same-template` | Renders selected items with the same template as dropdown options (`render.item = render.option`). |
| `select-preload-values` | Triggers an AJAX preload of the listed value IDs (see below). Its value is a comma-separated list of IDs. |

### `select-*` options

All attributes prefixed `select-` are collected into the Tom Select options object (keys are camel-cased: `select-query-route` becomes `queryRoute`). Recognized keys used by the script:

| Key (attribute) | Used as | Purpose |
| --- | --- | --- |
| `select-query` | `options.query` | Encrypted query payload; its presence switches the select into remote AJAX mode. |
| `select-query-route` | `options.queryRoute` | Endpoint URL for the search/`load` request. |
| `select-fetch-route` | `options.fetchRoute` | Endpoint URL used to preload selected values by ID. |
| `select-search-field` | `options.searchField` | Extra fields to search on (merged with `__text`, `__value`). |
| `select-limit` | `options.limit` | Optional result limit appended to the search request. |

Any other `select-*` attribute is merged into the Tom Select options verbatim.

### `bind-*` options (dependent selects)

Attributes prefixed `bind-` declare bindings to other inputs. Each binding may be a literal value, an object with a `value`, or an object with a `selector` pointing at another field. When a bound field's value changes, the select is cleared, its options are cleared, the `bind-<name>.value` attribute is updated, the loaded-search cache is reset (`instance.loadedSearches = {}`), and the `preloaded` class is removed from the `.ts-wrapper`.

## Tom Select options applied

The initiator builds `defaultOptions`, then merges in `select-*` selector options and any options passed by the caller (`_.merge(defaultOptions, selectorOptions, options)`):

```js
const defaultOptions = {
    create: $select.hasAttr('tags'),
    dropdownParent: 'body',
    copyClassesToDropdown: false,
    placeholder: __('Select an option'),
    onInitialize: function () {
        const selected = $select.find('option[selected]');
        if (selected.length === 0 && $select.hasAttr('removable')) {
            this.clear();
        }
    },
    render: {
        option: function (data, escape) {
            return `<div>${data.__html || escape(data.__text || data.text)}</div>`;
        },
    },
};
```

Notes:
- The placeholder text is localized through the global `__()` translation helper (`public/assets/js/functions.js`), so locale strings come from the app's translations. There is no explicit RTL/`dir` handling in this script; direction is inherited from the surrounding page/layout.
- The dropdown is appended to `body` (`dropdownParent: 'body'`) and option/host classes are not copied (`copyClassesToDropdown: false`).
- The `render.option` function prefers a server-supplied `data.__html`, falling back to the escaped `data.__text` or `data.text`.

## Remote (AJAX) mode

When `options.query` is present (from `select-query`), `prepareRemoteOptions()` merges these settings:

```js
{
    preload: 'focus',
    placeholder: __('Type to search...'),
    valueField: '__value',
    labelField: '__text',
    searchField: ['__text', '__value', ...fields],
    load: function (term, callback) { /* fetch from queryRoute */ },
}
```

The `load` function builds a URL from `options.queryRoute` and appends:
- `term` — the typed search term
- `data` — the encrypted `options.query` payload
- `limit` — only if `options.limit` is set
- `parameters[<key>]` — one per binding from `getSelectBindings($select)`

It expects a JSON response and passes `response.payload.data` to the Tom Select callback (empty array on error).

## Preloading selected values

If the `select-preload-values` attribute is present (a comma-separated list of IDs), `preloadValues()` fetches from `options.fetchRoute` with `data` (the query payload), `ids`, and the `parameters[...]` bindings, then calls `instance.addOptions(data)` and `instance.setValue(...)` on the returned `response.payload.data`. Empty ID lists are skipped.

## Events

After construction, the script stores the instance with `$select.data('tomselect', instance)` and binds two jQuery events on the select element:

| Event | Behavior |
| --- | --- |
| `select:preload` | Re-runs `preloadValues($select)`. Trigger this to (re)load selected values on demand. |
| `visibility:updated` | Handler receives `(event, visibility)`; calls `instance.enable()` when truthy, otherwise `instance.disable()`. Driven by the platform's visibility watcher. |

## Triggered by the Select component

The `<x-select>` Blade component (`app/View/Components/Select.php`) renders a `<select>` and, when its `tom` prop is `true` (the default), merges `init => 'tomselect'` onto the element. When a `query` is provided it also emits the `select-query`, `select-query-route`, `select-fetch-route`, `select-search-field`, and `select-preload-values` attributes that drive remote mode and preloading. Setting `floating` forces `tom = false`, disabling Tom Select.

### Real examples from the app

Static options with a removable single select:

```blade
<x-select name="role" :title="__('Role')" :options="$roles" :value="old('role', $entry?->roles()->first()?->name)" removable />
```

Tags + multiple:

```blade
<x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="old('tags', $entry?->tags)" tags multiple />
```

Remote query-backed select with a custom item template (from `resources/components/countries.blade.php`):

```blade
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

## Gotchas

- The initiator only runs when an element has `init="tomselect"`. The `init()` scanner skips elements already marked `initialized`, so dynamically inserted selects are picked up by the MutationObserver-driven re-init in `app.js`.
- Remote mode is keyed on the presence of `options.query`; without `select-query` the select operates on its static `<option>` children only.
- `removable` without any selected option clears the value during `onInitialize`.
- This script does not set `dir`/RTL; rely on the page direction. Placeholders are translated via `__()`.

## Related

- [Select component](/components/select) — the Blade component that emits `init="tomselect"` and the `select-*`/`bind-*` attributes.
- [Frontend platform / asset system](/frontend/asset-system) — how `init()`, `window.__inits`, `getOptionsFromSelector`, and `__()` are loaded.
