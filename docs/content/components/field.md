# Field

`<x-field>` wraps any control in the standard form-field frame: a label above it and a hint below it. Every built-in form component uses it, so wrapping your own custom control in `<x-field>` makes it line up with the rest of the form.

## Usage

```blade
<x-field id="budget" :title="__('Budget')" :hint="__('In US dollars')" :required="true">
    <input type="range" id="budget" name="budget" class="form-range" min="0" max="10000" />
</x-field>
```

The control goes in the default slot. The label and hint are only shown when you pass `title` and `hint`.

## Options

- **`title`** — label text shown above the control.
- **`id`** — the control's id, so clicking the label focuses it.
- **`hint`** — helper text shown below the control.
- **`required`** — show the required marker on the label. This is visual only; add `validation="required"` to the control for actual validation.

## Examples

### Custom control with validation

```blade
<x-field id="terms" :title="__('Terms')" :required="true">
    <label class="form-check">
        <input type="checkbox" id="terms" name="terms" class="form-check-input" validation="required" />
        <span class="form-check-label">{{ __('I agree to the terms') }}</span>
    </label>
</x-field>
```

## Related

- [Label](/components/label) — the label `<x-field>` renders.
- [Hint](/components/hint) — the hint `<x-field>` renders.
- [Components overview](/components/overview) — shared form-field conventions.
