# Icon Picker

`<x-icon-picker>` is a text field bound to a Font Awesome icon, with a live
preview and a search button that opens a modal to browse and pick free Font
Awesome icons. The selected icon class string (e.g. `far fa-note-sticky`) is
written back into the field.

## Usage

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). The stored value is a full Font
Awesome class string (style + name), so seed `value` with that full string. The
picker initializes itself through the [asset & init system](/frontend/asset-system).

## Options

- **`title`** — label shown above the field.
- **`hint`** — helper text shown below the field.
- **`value`** — the current icon class string (e.g. `far fa-note-sticky`).
- **`id`** — element id; auto-generated when omitted.

## Examples

### Icon field with a default

```blade
<x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'far fa-note-sticky')" />
```

### With a hint and required marker

```blade
<x-icon-picker
    name="icon"
    :title="__('Icon')"
    :hint="__('Pick a FontAwesome icon')"
    validation="required"
    :value="old('icon')"
/>
```

The icon search needs network access to the Font Awesome API; only free icons
are returned.

## Related

- [Icon Picker init](/frontend/inits/icon-picker) — the JS behind the picker.
- [Icon](/components/icon) — render a single icon.
- [Components overview](/components/overview) — shared form-field conventions.
