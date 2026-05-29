# Toggle

The `<x-toggle>` component renders a Bootstrap "switch" style checkbox with an optional label, hint, and distinct on/off captions. It is backed by the `App\View\Components\Toggle` class.

## What it is

`<x-toggle>` produces a `<label class="form-check form-switch">` wrapping a single `<input type="checkbox">` plus two caption spans (one shown when on, one when off). It optionally renders an `<x-label>` above the control and an `<x-hint>` below it. Because it is a plain checkbox, it submits its value in standard form posts and integrates with Bootstrap's switch styling — no JavaScript plugin is required.

## Props

The props are the constructor arguments of `App\View\Components\Toggle`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` → auto `uniqid('toggle-')` | The `id` of the `<input>` and the `for` target of the label. Auto-generated if omitted. |
| `title` | `?string` | `null` | When set, renders an `<x-label :title="$title" :for="$id" :required="$required" />` above the toggle. |
| `hint` | `?string` | `null` | When set, renders `<x-hint class="mt-1">` below the toggle. |
| `value` | `?bool` | `false` | The checked state. When truthy, the input renders `checked`. |
| `on` | `?string` | `null` → `__('Enabled')` | Caption shown in the "on" label span. |
| `off` | `?string` | `null` → `__('Disabled')` | Caption shown in the "off" label span. |

### Derived behavior

- **`required`** is not a prop — it is computed in `render()` as `str_contains($attributes->get('validation') ?: '', 'required')`. Pass a `validation="required"` attribute to mark the rendered `<x-label>` as required.
- Any extra HTML attributes (e.g. `name`, `class`, `permissions-toggle`) are forwarded via `$attributes`. The `class` attribute is merged onto the outer `<label>` (`form-check form-switch`) and the `<input>` receives `form-check-input`. Note that custom classes land on the wrapper label, not the input.
- There is **no slot**; the control is fully driven by props/attributes.
- This is a checkbox-based control, so it has no Livewire-specific binding logic of its own — apply `wire:model` (or `name`) directly as a forwarded attribute when needed.

## Rendered markup

```blade
@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<label {{ $attributes->class(['form-check form-switch']) }}>
    <input type="checkbox" id="{{ $id }}" @checked($value)
        {{ $attributes->class(['form-check-input']) }} />
    <span class="form-check-label form-check-label-on">{{ $on ?: __('Enabled') }}</span>
    <span class="form-check-label form-check-label-off">{{ $off ?: __('Disabled') }}</span>
</label>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif
```

## Usage

Basic toggle bound to a form field with custom on/off captions, defaulting to on:

```blade
<x-toggle name="active" :title="__('Active')"
    :value="old('active', $entry?->active ?? true)"
    :on="__('Yes')" :off="__('No')" />
```

A settings toggle whose state comes from a `setting()` value (uses the default `Enabled`/`Disabled` captions):

```blade
<x-toggle name="page_loader_enabled" :title="__('Page loader')" :value="setting('page_loader_enabled')" />
```

```blade
<x-toggle name="service_worker_enabled" :title="__('Service Worker')" :value="setting('service_worker_enabled')" />
```

A title-less, reverse-aligned toggle used as a "select all" control, carrying a custom `permissions-toggle` attribute that page-level JS hooks into:

```blade
<x-toggle class="form-check-reverse mb-0" :on="__('All')" :off="__('All')" permissions-toggle />
```

## Gotchas

- Omitting `id` is safe — a unique `toggle-*` id is generated and wired to the label automatically.
- To show the label as required, you must pass `validation="required"`; setting `title` alone does not mark it required.
- Custom `class` values are applied to the outer `<label>` wrapper (alongside `form-check form-switch`), so use Bootstrap layout helpers like `form-check-reverse` or `mb-0` there.

## Related

- [Label component](/components/label)
- [Hint component](/components/hint)
