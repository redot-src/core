# Query Builder Initializer

The `query-builder` initializer wires up the [jQuery QueryBuilder](https://querybuilder.js.org/) vendor plugin for the dashboard's query-builder form field. It reads its configuration from `query-*` attributes on a hidden input, renders the visual rule builder, and serializes the rules back into that input on form submit.

## What it is

The script at `public/assets/inits/query-builder.js` is an init module registered under the key `query-builder` on the global `window.__inits` registry. It is invoked by the shared `init()` runner (`public/assets/js/functions.js`) for any element carrying `init="query-builder"`.

It also registers two small jQuery plugin shims that QueryBuilder uses internally for its rule inputs:

- `$.fn.tomselect(options)` — delegates to the `tomselect` init (`window.__inits['tomselect']`) to turn a `<select>` rule input into a [TomSelect](https://tom-select.js.org/) box. Skips elements already bound (`$select.data('tomselect')`) and logs `TomSelect init is not loaded.` if the init is missing.
- `$.fn.datepicker(options)` — delegates to the `tempus-dominus` init (`window.__inits['tempus-dominus']`) to attach a [Tempus Dominus](https://getdatepicker.com/) date picker. Skips elements already bound (`$input.data('picker')`) and logs `Tempus Dominus init is not loaded.` if missing.

## How it auto-initializes

The init runner scans the DOM for `[init]:not([initialized])` elements and calls the matching `window.__inits[name]` with the element and any inline options. The query-builder field's hidden input is rendered with `init="query-builder"` by the `<x-query-builder>` Blade component, so it is picked up automatically.

The initializer expects this DOM structure (produced by the component):

```html
<div query-builder-container>
    <input type="hidden" id="..." value="..." init="query-builder" query-filters="[...]" />
    <div query-builder></div>
</div>
```

The hidden input is the selector passed to the init. The visual builder is rendered into the sibling `[query-builder]` element, found via:

```js
let $builder = $(selector).closest('[query-builder-container]').find('[query-builder]');
```

## Options

Default options merged inside the init:

```js
const defaultOptions = {
    lang_code: $('html').attr('lang'),
    display_empty_filter: false,
    display_errors: true,
    allow_groups: 2,
    allow_empty: true,
    default_filter: null,
    icons: {
        add_group: 'fas fa-folder-plus',
        add_rule: 'fas fa-plus-circle',
        remove_group: 'fas fa-trash',
        remove_rule: 'fas fa-times',
    },
};
```

These are then merged (via `_.merge`) with two further sources, in increasing precedence:

1. `defaultOptions` (above).
2. Options read from the input's `query-*` attributes via `getOptionsFromSelector(selector, 'query-')`. Attribute names have the `query-` prefix stripped and are camel-cased into nested option keys. This is how the component's `query-filters="[...]"` attribute becomes `options.filters`.
3. Inline options passed to the initializer call.

### Locale / RTL handling

`lang_code` defaults to the document language: `$('html').attr('lang')`. QueryBuilder uses this code to select its bundled language pack, so the builder labels (operators, buttons) follow the page locale. There is no explicit RTL flag in this script; direction is inherited from the page/CSS, and the `lang_code` drives any localized strings.

## Filters, value setter / getter

For every entry in `options.filters`, the init attaches a custom `valueSetter` and `valueGetter` so rule values round-trip through the actual input/select widgets:

```js
filter.valueSetter = function (rule, value) {
    let count = rule.operator.nb_inputs;
    let values = count > 1 ? value : [value];
    let $inputs = rule.$el.find('.rule-value-container').find('input, select');
    $inputs.each((index, element) => setFieldValue($(element), values[index]));
};

filter.valueGetter = function (rule) {
    let count = rule.operator.nb_inputs;
    let $inputs = rule.$el.find('.rule-value-container').find('input, select');
    let values = [];
    $inputs.each((index, element) => values.push(getFieldValue($(element))));
    return count > 1 ? values : values[0];
};
```

`setFieldValue` / `getFieldValue` are the shared helpers in `public/assets/js/functions.js`. Operators with `nb_inputs > 1` (e.g. `between`) produce/consume an array of values; single-input operators use a scalar.

## Initial value and form serialization

- Initial rules: if the hidden input already has a value, it is parsed as JSON into `options.rules`; otherwise an empty array is used.

  ```js
  options.rules = $(selector).val() ? JSON.parse($(selector).val()) : [];
  ```

- On submit of the closest `<form>`, the builder's rules are read and written back to the hidden input as a JSON string (or `null` when empty):

  ```js
  $form.on('submit', () => {
      let rules = instance.getRules();
      let value = _.isEmpty(rules) ? null : JSON.stringify(rules);
      $(selector).val(value);
  });
  ```

- The QueryBuilder instance is stored on the input via `$(selector).data('queryBuilder', instance)` for later access.

## Usage

The initializer is not called by hand. Render the `<x-query-builder>` component and the `init="query-builder"` attribute it emits triggers the init automatically. The component's `:filters` / `:model` props are resolved server-side (via `Redot\Support\QueryFilters`) into the `query-filters` attribute, which the init reads as `options.filters`.

```blade
<x-query-builder
    title="Audience"
    model="App\Models\User"
    :value="$segment->rules"
/>
```

The hidden input that the init binds to is rendered as:

```blade
<div query-builder-container>
    <input type="hidden" id="{{ $id }}" value="{{ $value }}" {{ $attributes->merge(['init' => 'query-builder']) }} />
    <div query-builder></div>
</div>
```

## Gotchas

- The init only runs against an input with `init="query-builder"`; it does not search for `[query-builder]` on its own. The hidden input is the source of truth, the `[query-builder]` div is just the render target.
- `valueSetter`/`valueGetter` iterate `.rule-value-container input, select`, so rule widgets must live there for values to round-trip.
- TomSelect and Tempus Dominus rule widgets only work if their inits (`tomselect`, `tempus-dominus`) are loaded; otherwise the shims log an error and do nothing. The component pushes the matching vendor scripts/styles (`tom-select`, `tempus-dominus`, `popper`, `query-builder`) via `@pushOnce`.
- Values are stored as a JSON string; an empty builder serializes to `null`.

## Related pages

- [Query Builder component](/components/query-builder)
- [TomSelect initializer](/frontend/inits/tomselect)
- [Tempus Dominus initializer](/frontend/inits/tempus-dominus)
