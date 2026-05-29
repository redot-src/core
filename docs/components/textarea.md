# Textarea

`<x-textarea>` renders a Bootstrap-styled multi-line text input, optionally wrapped with a [label](/components/label) and a [hint](/components/hint). It can auto-grow its height as the user types via Tabler's `autosize` toggle.

## What it is

A class-based Blade component backed by `App\View\Components\Textarea`, rendering the view `resources/components/textarea.blade.php`. The class extends the local `App\View\Components\Component` base (which adds an `attributes()` helper for mutating the attribute bag during render).

The view outputs:

- An optional `<x-label>` when a `title` is set.
- A `<textarea>` with the `form-control` class merged in.
- An optional `<x-hint>` when a `hint` is set.

## Props

These come from the constructor of `App\View\Components\Textarea`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` | The textarea `id`. When omitted, a unique id is generated via `uniqid('textarea-')`. Also used as the label's `for` target. |
| `title` | `?string` | `null` | Label text. When set, a `<x-label>` is rendered above the field. |
| `hint` | `?string` | `null` | Helper text rendered below the field inside `<x-hint class="mt-1">`. |
| `value` | `?string` | `null` | Initial textarea content. If falsy, the component falls back to the default slot content. |
| `autosize` | `bool` | `false` | When `true`, merges `data-bs-toggle="autosize"` onto the textarea so its height grows with content. |

### Derived behavior

- **`required`** — not a prop. Inside `render()`, the view receives a `required` flag computed as `str_contains($attributes->get('validation'), 'required')`. So passing a `validation` attribute containing `required` automatically marks the rendered label as required. Example: `validation="required|max:500"`.

### Attributes / slots

- **Slot** — the default slot is used as the textarea body when `value` is empty: `{!! $value ?: $slot !!}`. Note the content is rendered unescaped.
- **Pass-through attributes** — any extra attributes (`name`, `placeholder`, `rows`, `validation`, `wire:model`, etc.) flow onto the `<textarea>` via the attribute bag. The `form-control` class is always merged in.
- **`wire:model`** — there is no special Livewire handling; bind it like any other attribute and it lands on the `<textarea>` directly.

## Autosize

Setting `:autosize="true"` adds `data-bs-toggle="autosize"`, which is bound by Tabler's bundled JavaScript (`public/vendor/tabler/js/tabler.min.js`). No custom init script is registered by the dashboard for this — it relies on the vendor Tabler bundle.

## Usage

Custom code settings (real usage from `resources/views/dashboard/settings/partials/custom-code.blade.php`):

```blade
<x-textarea name="head_code" :title="__('Head code')" value="{{ setting('head_code') }}" />

<x-textarea name="body_code" :title="__('Body code')" value="{{ setting('body_code') }}" />
```

The [rich editor](/components/rich-editor) component is built on top of `<x-textarea>`, forwarding only the `name` and `validation` attributes and explicitly disabling autosize (real usage from `resources/components/rich-editor.blade.php`):

```blade
<x-textarea :value="$value" :id="$id" :autosize="false" init="tinymce"
    {{ $attributes->only(['name', 'validation']) }} />
```

A required, auto-growing textarea with a hint:

```blade
<x-textarea
    name="bio"
    :title="__('Biography')"
    :hint="__('Tell us about yourself')"
    :autosize="true"
    validation="required|max:500"
    rows="4"
/>
```

## Gotchas

- The slot content is echoed unescaped (`{!! ... !!}`); only use it for trusted content.
- `required` is driven by the `validation` attribute string, not by a dedicated prop — there is no standalone `required` prop on this component.
- Autosize depends on the Tabler vendor bundle being loaded; without it, `data-bs-toggle="autosize"` has no effect.

## Related

- [Label component](/components/label)
- [Hint component](/components/hint)
- [Input component](/components/input)
- [Rich editor component](/components/rich-editor)
