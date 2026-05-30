# Tomselect Initializer

`tomselect` upgrades a `<select>` into a searchable, tag-friendly select (powered by [Tom Select](https://tom-select.js.org/)) with optional remote search and dependent selects. It backs the [`<x-select>` component](/components/select), which enables it by default.

## Enable it

You rarely write `init="tomselect"` yourself — render the [`<x-select>` component](/components/select). It adds the attribute (and, for remote selects, the `select-*` attributes) for you:

```blade
<x-select name="role" :title="__('Role')" :options="$roles" :value="old('role', $current)" removable />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Toggle behavior with these flag attributes on the `<select>` (the component exposes them as props):

- **`tags`** — let users create new options by typing.
- **`multiple`** — standard multi-select with removable chips.
- **`removable`** — add a clear button to a single select.
- **`same-template`** — render selected items with the same template as the dropdown options.
- **`select-preload-values`** — comma-separated IDs to load on init (remote mode).

For remote, query-backed selects the component emits these `select-` attributes:

- **`select-query`** / **`select-query-route`** — switch the select into AJAX search mode and name the search endpoint.
- **`select-fetch-route`** — endpoint used to preload selected values by ID.
- **`select-search-field`** — extra fields to match the typed term against.
- **`select-limit`** — cap on results per search.

Use `bind-*` attributes to make one select depend on another field (the dependent select clears and reloads when the bound field changes).

```blade
{{-- Tags + multiple --}}
<x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="old('tags', $entry?->tags)" tags multiple />

{{-- Remote, query-backed --}}
<x-select :query="\App\Models\Country::class" key="code" template="country" same-template />
```

The placeholder follows the page locale. Trigger `select:preload` on the element to reload selected values on demand.

## Related

- [Select component](/components/select) — the component that enables this and emits the `select-*` / `bind-*` attributes.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
