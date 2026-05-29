# Select

`<x-select>` renders an enhanced HTML `<select>` element. By default it boots the [Tom Select](https://tom-select.js.org/) JavaScript widget for searchable, taggable dropdowns, and it can optionally load its options remotely from an Eloquent/Query Builder query.

## What it is

The component is backed by the PHP class `App\View\Components\Select` (`app/View/Components/Select.php`) which renders the view `resources/components/select.blade.php`. It supports three modes:

- **Static options** — pass an `:options` array/collection and the matching `:value`.
- **Remote query** — pass a `:query` (model class, Eloquent or Query Builder) and the dropdown loads/searches results over AJAX through the `global.select.*` routes.
- **Floating label** — set `floating` to use Bootstrap's `.form-floating` layout (this disables Tom Select).

## Props

All constructor arguments of `App\View\Components\Select` are public props. Any extra HTML attributes (`name`, `multiple`, `validation`, `removable`, `tags`, `same-template`, etc.) are forwarded to the underlying `<select>` element via the attribute bag.

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | auto `uniqid('select-')` | The `id` of the `<select>`, also used by the label's `for`. |
| `title` | `?string` | `null` | Renders an `<x-label>` above (or, when floating, inside) the field. |
| `hint` | `?string` | `null` | Renders an `<x-hint>` below the field. |
| `options` | `array\|Collection` | `[]` | Key/value map; key becomes `<option value>`, value the label. |
| `value` | `array\|string\|null` | `[]` | Selected value(s). A string is split on commas and run through `parse_csv()`; non-arrays are wrapped into an array. Compared with `in_array` to mark `@selected`. |
| `tom` | `bool` | `true` | Enable the Tom Select widget. Forced to `false` when `floating` is `true`. |
| `floating` | `bool` | `false` | Use the Bootstrap floating-label layout. |
| `query` | `EloquentBuilder\|QueryBuilder\|string\|null` | `null` | Remote data source. A class-string is resolved via `app(...)->query()`. Switches the component into remote/AJAX mode. |
| `search` | `array\|string` | `[]` | Column(s) searched on the server. A string is parsed as CSV. |
| `appends` | `array\|string` | `[]` | Extra attributes appended to each remote result row. CSV strings parsed. |
| `key` | `string` | `'id'` | Column used as the option value in remote mode. |
| `text` | `string` | `'name'` | Column used as the option label in remote mode. |
| `template` | `?string` | `null` | Blade view used to render each remote option. Auto-prefixed with `templates.select.` unless it already starts with `templates.select`. Must exist or an `InvalidArgumentException` is thrown. |

### Derived behavior

- **`required`** — computed automatically: the label/field is marked required when the `validation` attribute contains the string `required` (e.g. `validation="required|email"`).
- **`tom` attributes** — when Tom Select is on, `init="tomselect"` is merged onto the `<select>`, which is what the JS init binds to. The Tom Select CSS/JS assets are pushed once via `plugins-styles` / `plugins-scripts` stacks (`hashed_asset('/vendor/tom-select/...')`).
- **Remote attributes** — when `query` is set, the component merges these attributes onto the element: `select-query` (encrypted query payload), `select-query-route` (`route('global.select.search')`), `select-fetch-route` (`route('global.select.fetch')`), `select-search-field` (JSON of `search`), and `select-preload-values` (comma-joined current `value`).

## Slots

- **Default slot** — appended after the generated `<option>`s inside the `<select>`, so you can add custom or static `<option>` entries alongside `:options`.

## JavaScript (Tom Select)

When `tom` is enabled, the element carries `init="tomselect"`, handled by `public/assets/inits/tomselect.js` which wraps the vendored Tom Select library. Notable attribute-driven behavior:

- `tags` — enables `create` (free-typed options allowed).
- `multiple` or `removable` — adds the `remove_button` plugin.
- `removable` — if nothing is pre-selected, the field is cleared on init (no forced default).
- `same-template` — renders the selected item using the same template as the dropdown option.
- Remote mode reads the `select-*` attributes to lazy-load (`preload: 'focus'`), search (`load`), and preload selected values. It also supports `bind-*` attributes to drive dependent selects and listens for `select:preload` and `visibility:updated` events.

There is no separate options object passed from Blade — configuration flows entirely through the rendered attributes.

## Examples

Static options with a default value and validation:

```blade
<x-select name="direction" :title="__('Direction')" :value="old('direction', 'ltr')" validation="required"
    :options="['ltr' => __('Left to Right'), 'rtl' => __('Right to Left')]" />
```

A clearable single select (no forced default) bound to a model relationship:

```blade
<x-select name="role" :title="__('Role')" :options="$roles" :value="old('role', $entry?->roles()->first()?->name)" removable />
```

A multiple, taggable select (note the `name="tags[]"` and array value):

```blade
<x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="old('tags', $entry?->tags)" tags multiple />
```

Numeric options with a default value:

```blade
<x-select name="size" :value="512" :title="__('Size')" :options="[
    128 => '128px',
    256 => '256px',
    512 => '512px',
    1024 => '1024px',
]" />
```

Remote query mode searching across multiple columns with a custom item template:

```blade
<x-select name="user_id" :title="__('User')" :query="$users" text="full_name" search="full_name, email"
    template="user" validation="required" />
```

Remote query via a model class-string (resolved server-side), with a shared dropdown/item template:

```blade
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

## Gotchas

- Setting `floating` forces `tom` off, so floating-label selects are plain Bootstrap selects with no Tom Select search/tags.
- In remote mode, `template` must reference an existing view (resolved under `templates.select.`) or rendering throws `InvalidArgumentException`.
- `value`, `search`, and `appends` accept CSV strings and are normalized to arrays at render time.

## Related

- [Input component](/components/input)
- [Label component](/components/label)
- [Hint component](/components/hint)
