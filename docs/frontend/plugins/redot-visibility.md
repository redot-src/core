# RedotVisibility

`RedotVisibility` is the dashboard's conditional-visibility plugin. It shows or hides any element that carries a `visible-when` attribute based on a live JavaScript expression evaluated against the current values of other form fields, re-running automatically whenever those fields change.

## What it is

The plugin is defined in `public/assets/plugins/redot-visibility.js` as the global class `RedotVisibility`. It is a singleton: the constructor returns the existing instance if one already exists, and the static `init()` is what creates/returns it.

It works entirely through HTML attributes — there is no Blade component named after it. You add a `visible-when` attribute to any element, give it an expression, and reference the fields it depends on with the `$field` token syntax.

## How it binds

- **Selector / attribute:** `visible-when` (stored in the `selector` property). Every element matching `[visible-when]` is managed.
- **Field token pattern:** `selectorPattern = /\$([^\s.]+)/g`. Inside the expression, `$something` is replaced with the JSON-encoded value of the field resolved by `$.query('something')`. `$.query` (defined in `functions.js`) tries the token as a raw selector, then `#id`, `.class`, `[name="…"]`, `[name^="…"]`, and `[token]`, returning the first match.
- **Value reading:** field values come from the global `getFieldValue()` helper, which understands checkboxes, radios, switches, number inputs, TinyMCE editors, and uploader fields.

When the plugin runs `watch()`, it scans every `[visible-when]` element, extracts the `$field` tokens, and attaches `change input` listeners to each referenced field (deduplicated via the `elements` Set) so edits trigger an `update()`.

## Public API

| Member | Type | Description |
| --- | --- | --- |
| `RedotVisibility.instance` | static | The singleton instance (or `null`). |
| `RedotVisibility.init()` | static | Creates the singleton and starts watching. Returns the instance. |
| `selector` | property | The bound attribute name. Default `'visible-when'`. |
| `selectorPattern` | property | Regex for field tokens. Default `/\$([^\s.]+)/g`. |
| `elements` | property | `Set` of field selectors already being watched. |
| `update()` | method | Re-evaluates every `[visible-when]` element and applies visibility. |
| `evaluate(statement)` | method | Substitutes `$tokens` and runs the expression via `new Function`. Returns `false` and logs on error. |
| `watch()` | method | Binds change/input listeners to referenced fields, then calls `update()`. |

### What `update()` does to each element

For every `[visible-when]` element it computes `visibility = Boolean(evaluate(statement))` and then:

- Sets `is-visible="true|false"` on the element and toggles it with `$element.toggle(visibility)`.
- For each descendant `input, select, textarea`: sets `is-visible`, and (unless the closest `form` has the `keep-hidden` attribute) toggles the field's `disabled` attribute and the validator's `disable-validation` attribute (`RedotValidator.disableAttribute`). Hidden fields are disabled so they are excluded from submission and validation.
- Triggers a `visibility:updated` event on both the fields and the element, passing the boolean as event data.

The CSS in `app.css` hides unevaluated elements up-front so there is no flash before JS runs:

```css
[visible-when]:not([is-visible='true']) {
    display: none;
}
```

## Events

- `visibility:updated` — triggered on each managed element and its inner fields, with the boolean visibility as the second handler argument. The dashboard listens for this globally in `app.js` to keep validation in sync:

```js
$(document).on('visibility:updated', '[visible-when]', (event, visible) => {
    const $container = $(event.target);
    const $targets = $container.is('[validation]') ? $container : $container.find('[validation]');

    if (visible) {
        $targets.removeAttr('disable-validation');
    } else {
        $targets.attr('disable-validation', true);
    }
});
```

## Bootstrapping

The script is loaded in `resources/layouts/scaffold.blade.php`:

```blade
<script src="{{ hashed_asset('/assets/plugins/redot-visibility.js') }}"></script>
```

It is initialized once on DOM ready in `app.js`:

```js
$(document).ready(() => {
    RedotVisibility.init();
    // ...
});
```

Because the dashboard re-runs initializers on DOM mutations, the global `init()` in `functions.js` re-arms the watcher after each pass so newly inserted `[visible-when]` elements are picked up:

```js
if (RedotVisibility.instance) RedotVisibility.instance.watch();
```

## Usage

Reference dependent fields with `$name` tokens and write any JS-evaluable expression. Tokens are replaced with the JSON value of the matched field before evaluation.

```blade
{{-- Show the "other reason" input only when the reason select is "other" --}}
<select name="reason">
    <option value="active">Active</option>
    <option value="other">Other</option>
</select>

<div visible-when="$reason === 'other'">
    <input name="other_reason" type="text" validation="required" />
</div>
```

```blade
{{-- Combine multiple fields in one expression --}}
<input name="amount" type="number" />
<input name="coupon" type="text" />

<div visible-when="$amount > 100 && $coupon.length > 0">
    <input name="note" type="text" />
</div>
```

```blade
{{-- Keep hidden fields enabled (do not disable/strip from submission) --}}
<form keep-hidden>
    <div visible-when="$type === 'company'">
        <input name="company_name" />
    </div>
</form>
```

## Repeater integration

`redot-repeater` is aware of the plugin (see `redot-repeater.js`). When cloning a repeater row it calls `handleVisibility($item)`: for each `[visible-when]` inside the new row it rewrites the `$field` tokens to point at the cloned field via a generated `visibility-key` attribute (`[visibility-key="visibility-N"]`), so each row's conditions track that row's own fields instead of leaking across rows.

## Gotchas

- The expression runs through `new Function(...)`, so it must be a valid JS expression. Errors are swallowed (logged to the console) and treated as `false`.
- A `$token` that resolves to no element yields `undefined` in the expression; tokens cannot contain whitespace or `.` (the pattern stops at those).
- Tokens are matched in attribute resolution order by `$.query`, so an ambiguous token (e.g. matching both an id and a name) resolves to the first hit.
- Hidden fields are `disabled` and given `disable-validation` by default; use the form-level `keep-hidden` attribute to opt out of that behavior.

## Related

- [RedotValidator](/frontend/plugins/redot-validator) — consumes the `disable-validation` attribute that this plugin toggles.
- [RedotRepeater](/frontend/plugins/redot-repeater) — rewrites `visible-when` tokens per cloned row.
