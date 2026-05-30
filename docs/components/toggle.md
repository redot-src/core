# Toggle

`<x-toggle>` renders a switch-style checkbox with an optional label, hint, and
distinct on/off captions. It submits as a normal checkbox, so it works in plain
form posts with no JavaScript.

## Usage

```blade
<x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" />
```

It shares the [common form-field attributes](/components/overview#shared-form-field-conventions)
(`name`, `title`, `value`, `hint`, `validation`). `value` is the checked state.

## Options

- **`title`** — label shown above the toggle.
- **`hint`** — helper text shown below the toggle.
- **`value`** — initial checked state.
- **`on`** — caption shown when on (defaults to "Enabled").
- **`off`** — caption shown when off (defaults to "Disabled").
- **`id`** — element id; auto-generated when omitted.

Any `class` you pass lands on the switch wrapper, so use Bootstrap layout helpers
like `form-check-reverse` or `mb-0` there.

## Examples

### Custom on/off captions

```blade
<x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
```

### Setting toggle with default captions

```blade
<x-toggle name="page_loader_enabled" :title="__('Page loader')" :value="setting('page_loader_enabled')" />
```

### Title-less "select all" control

```blade
<x-toggle class="form-check-reverse mb-0" :on="__('All')" :off="__('All')" permissions-toggle />
```

## Related

- [Checkboxes](/components/checkboxes) — a group of related checkboxes.
- [Components overview](/components/overview) — shared form-field conventions.
