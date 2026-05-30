# Query Builder Initializer

`query-builder` renders a visual rule builder (powered by [jQuery QueryBuilder](https://querybuilder.js.org/)) and serializes the rules back into a hidden field as JSON. Reach for it to let users assemble conditions — segments, filters, audiences. It backs the [`<x-query-builder>` component](/components/query-builder).

## Enable it

You don't enable this by hand — render the [`<x-query-builder>` component](/components/query-builder). It resolves the available filters from a model server-side, emits `init="query-builder"`, and loads the vendor assets:

```blade
<x-query-builder title="Audience" model="App\Models\User" :value="$segment->rules" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Configuration mostly comes from the component (the filter list is derived from the `model`). To tune the builder, set these as `query-` attributes:

- **`query-filters`** — the JSON filter definitions (the component fills this from `:model`/`:filters`).
- **`query-allow-groups`** — how many levels of nested groups are allowed.
- **`query-allow-empty`** — permit saving with no rules.
- **`query-default-filter`** — which filter a new rule starts on.

Operator labels and buttons follow the page locale automatically. Rule values store as a JSON string; an empty builder saves as `null`.

## Related

- [Query Builder component](/components/query-builder) — the component that enables this for you.
- [Tom Select init](/frontend/inits/tomselect) — used for `<select>` rule inputs.
- [Tempus Dominus init](/frontend/inits/tempus-dominus) — used for date rule inputs.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
