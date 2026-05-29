# Countries

`<x-countries>` is a thin, country-aware wrapper around the [Select component](/components/select). It renders a searchable, server-backed dropdown of countries — each option showing the country flag next to its name — and stores the selected country's ISO code as the value.

## What it is

`countries.blade.php` is an **anonymous Blade component** (there is no `app/View/Components/Countries.php` class). Its entire body is a pre-configured `<x-select>` plus a one-time stylesheet push for the flag icons:

```blade
@pushOnce('plugins-styles', 'tabler-flags-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler/css/tabler-flags.min.css') }}" />
@endPushOnce

<x-select :query="\App\Models\Country::class" key="code" template="country" same-template {{ $attributes }} />
```

Because it forwards <span v-pre>`{{ $attributes }}`</span>, every attribute and prop accepted by `<x-select>` is also accepted by `<x-countries>`. The component only hard-codes the country-specific bits:

| Forwarded to `<x-select>` | Value | Meaning |
| --- | --- | --- |
| `:query` | `\App\Models\Country::class` | Options are loaded server-side from the `Country` Eloquent model via the remote select endpoints. |
| `key` | `code` | The selected/submitted value is the country's ISO `code` (e.g. `eg`, `us`), not its primary key. |
| `template` | `country` | Each option is rendered with `resources/templates/select/country.blade.php` (flag + name). |
| `same-template` | (present) | The selected item is rendered with the same flag template as the dropdown options. |

It also pushes the Tabler flags CSS once per page (`tabler-flags-styles`), which provides the `flag flag-country-{code}` classes used by the option template.

## Props / attributes

There are no props unique to `<x-countries>`. All props come from the underlying `<x-select>` (`App\View\Components\Select`) constructor and may be passed through:

| Prop | Type | Default | Notes |
| --- | --- | --- | --- |
| `id` | `?string` | auto `select-…` | Generated with `uniqid('select-')` if omitted. |
| `title` | `?string` | `null` | Renders an `<x-label>` above the field. |
| `hint` | `?string` | `null` | Renders an `<x-hint>` below the field. |
| `value` | `array\|string\|null` | `[]` | Selected country code(s). Accepts a CSV string or array; pre-loads via `select-preload-values`. |
| `tom` | `bool` | `true` | Enables the Tom Select enhancement (`init="tomselect"`). |
| `floating` | `bool` | `false` | Floating-label layout; disables `tom` when true. |
| `search` | `array\|string` | `[]` | Extra columns to search against (CSV or array). |
| `appends` | `array\|string` | `[]` | Extra model attributes appended to each option payload. |
| `name` / validation attrs | — | — | Any standard HTML/validation attribute passes through the attribute bag (e.g. `name`, `validation`, `multiple`, `removable`). |

> The `key`, `template`, `query`, and `same-template` values are fixed by `<x-countries>`. Overriding them is not the intended use — pass other attributes instead.

### Selecting multiple countries

`<x-select>`'s JS init turns on the `remove_button` plugin when the element has `multiple` or `removable`. Since `<x-countries>` forwards attributes, add `multiple` to allow selecting several countries:

```blade
<x-countries name="countries[]" multiple title="Allowed countries" />
```

## How the data flows

Because `query` is set, `<x-select>` emits remote-search attributes on the `<select>` element:

- `select-query` — an encrypted, JSON-encoded payload describing the `Country` query, key/text columns, and the `country` template.
- `select-query-route` → `route('global.select.search')`
- `select-fetch-route` → `route('global.select.fetch')`
- `select-search-field` — JSON-encoded list of searchable columns.
- `select-preload-values` — comma-joined currently selected codes.

The browser-side [tomselect init](/frontend/inits/tomselect) reads these `select-*` attributes, fetches/searches options remotely, and renders each option through the `country` template. Because `same-template` is present, the init copies the option renderer to the selected-item renderer:

```js
if ($select.hasAttr('same-template')) {
    defaultOptions.render.item = defaultOptions.render.option;
}
```

So the chosen value also displays its flag.

## The option template

`resources/templates/select/country.blade.php` receives each `$item` (a `Country`) and renders the flag plus name:

```blade
<div class="d-flex align-items-center gap-2">
    <span class="flag flag-xs flag-country-{{ $item->code }}"></span>
    <span>{{ $item->name }}</span>
</div>
```

The `flag flag-country-{code}` classes come from the Tabler flags stylesheet the component pushes.

## Usage

Basic single-country picker bound to a form field:

```blade
<x-countries name="country" title="Country" />
```

Pre-selecting a value (country code):

```blade
<x-countries name="country" title="Country" :value="$user->country" />
```

Multiple selection with a hint:

```blade
<x-countries
    name="countries[]"
    multiple
    title="Operating countries"
    hint="Select all countries where the service is available."
/>
```

> Note: at the time of writing, no `<x-countries>` usages exist in the dashboard's `resources/views` — the examples above follow the real attribute contract of the underlying `<x-select>`.

## Gotchas

- The submitted value is the ISO `code` (e.g. `eg`), not the `Country` primary key, because `key="code"`.
- The Tabler flags CSS is pushed via `@pushOnce` to the `plugins-styles` stack — make sure your layout renders that stack, or flags will not appear.
- Disabling `tom` (`:tom="false"`) or setting `floating` falls back to a native select that does **not** perform remote search or render flag templates.

## Related

- [Select component](/components/select) — the underlying component and all its props.
- [tomselect init](/frontend/inits/tomselect) — the JS that powers remote search and template rendering.
