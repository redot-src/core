# Icon Picker Initializer

`icon-picker` turns a text input into a Font Awesome icon picker — a live preview plus a search modal — powered by the [RedotIconPicker plugin](/frontend/plugins/redot-icon-picker). It backs the [`<x-icon-picker>` component](/components/icon-picker).

## Enable it

Mark the input with `init="icon-picker"`. You rarely write this yourself — the `<x-icon-picker>` component adds it (along with the preview/modal markup the plugin needs) for you:

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $post?->icon ?? 'far fa-note-sticky')" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

Set these as `iconpicker-` attributes on the input:

- **`iconpicker-version`** — Font Awesome version to search against.
- **`iconpicker-max-results`** — how many icons a search returns at most.
- **`iconpicker-search-debounce`** — delay (ms) before a keystroke triggers a search.
- **`iconpicker-endpoint`** — Font Awesome API base URL, if you proxy it.

```blade
<x-icon-picker name="icon" iconpicker-max-results="200" iconpicker-version="6.4.2" />
```

## Related

- [Icon Picker component](/components/icon-picker) — the component that enables this for you.
- [RedotIconPicker plugin](/frontend/plugins/redot-icon-picker) — the picker behavior and instance API.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
