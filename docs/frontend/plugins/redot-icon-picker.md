# RedotIconPicker

The client-side picker that powers the [`<x-icon-picker>` component](/components/icon-picker): a live icon preview plus a search modal backed by the Font Awesome API. The chosen icon's class string (e.g. `fa-solid fa-house`) is written back into the input. Reach for the component — you almost never touch this plugin directly.

## Usage

Render the component; it loads the script and wires the [`icon-picker` init](/frontend/inits/icon-picker) for you. Pass the starting icon as the value:

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'far fa-note-sticky')" />
```

## Options

Set these as `iconpicker-` attributes on the input (or on the component tag):

- **`iconpicker-version`** — Font Awesome version to search against (defaults to a recent 6.x).
- **`iconpicker-max-results`** — how many icons a search returns at most.
- **`iconpicker-search-debounce`** — delay (ms) before a keystroke triggers a search.
- **`iconpicker-endpoint`** — Font Awesome API base URL, if you proxy it.

## Reaching the instance

The picker is stored on the input, so you can call into it later:

```js
const picker = $('#my-icon-input').data('iconPicker');
```

## Gotchas

- Search hits the live Font Awesome API; only free icons are listed, and a failed request shows an empty result rather than an error toast.
- Selection is single-choice.

## Related

- [Icon Picker component](/components/icon-picker) — the Blade component this powers.
- [Icon Picker init](/frontend/inits/icon-picker) — the `init="icon-picker"` that triggers it.
- [Asset & Init System](/frontend/asset-system) — how the script is loaded and wired.
