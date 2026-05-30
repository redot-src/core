# RedotVisibility

Conditional visibility for form markup: show or hide any element based on the live values of other fields. Reach for it when a field, section, or card should only appear once another field has a certain value. It works entirely through attributes — there is no component and nothing to initialize.

## Usage

Put a `visible-when` attribute on the element you want to toggle and write a JavaScript expression. Reference other fields with `$name` tokens — each token is replaced with that field's current value, and the expression re-runs whenever a referenced field changes:

```blade
<select name="reason">
    <option value="active">Active</option>
    <option value="other">Other</option>
</select>

<div visible-when="$reason === 'other'">
    <input name="other_reason" type="text" validation="required" />
</div>
```

Combine several fields in one expression:

```blade
<input name="amount" type="number" />
<input name="coupon" type="text" />

<div visible-when="$amount > 100 && $coupon.length > 0">
    <input name="note" type="text" />
</div>
```

A `$token` resolves the field by selector, then `#id`, `.class`, `[name="…"]`, and so on — usually just use the field's `name`.

## Hidden fields and validation

While an element is hidden its inputs are disabled, so they are excluded from both submission and [client-side validation](/frontend/plugins/redot-validator). That is what you want most of the time. To keep hidden fields enabled and submitted, set `keep-hidden` on the surrounding `<form>`:

```blade
<form keep-hidden>
    <div visible-when="$type === 'company'">
        <input name="company_name" />
    </div>
</form>
```

## Gotchas

- The expression is real JavaScript; a syntax error is treated as `false` (and logged).
- Tokens cannot contain spaces or dots.
- Inside a [repeater](/frontend/plugins/redot-repeater) row, `visible-when` tokens are rewritten per row automatically, so each row's conditions track its own fields.

## Related

- [RedotValidator](/frontend/plugins/redot-validator) — hidden fields are skipped from validation.
- [RedotRepeater](/frontend/plugins/redot-repeater) — per-row visibility.
- [Asset & Init System](/frontend/asset-system) — how front-end scripts load.
