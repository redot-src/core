# Rating

`<x-rating>` renders a star-rating input as a group of native radio buttons. It is a class-based Blade component backed by `App\View\Components\Rating`, styled with a CSS custom property for star size and an icon per star.

## What it is

The component outputs an optional [label](/components/label), a `.rating-field` container holding one radio `<input>` per star (rendered highest-to-lowest), each paired with a `<label>` containing an [icon](/components/icon), and an optional [hint](/components/hint) below.

Because each star is a real `<input type="radio">` sharing the same `name`, the field submits the selected star count as a standard form value — no JavaScript plugin is required. The stars are rendered in descending order (`$stars … 1`), which is the conventional markup pattern that lets CSS highlight the hovered/selected star and all stars before it.

## Props

All props come from the constructor of `App\View\Components\Rating`:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `?string` | `null` → `uniqid('rating-')` | DOM id of the `.rating-field` container; also used as the `for` target of the label and as the prefix for each radio's id (`{id}-{i}`). Auto-generated when omitted. |
| `title` | `?string` | `null` | Label text. When set, an `<x-label>` is rendered above the field. |
| `hint` | `?string` | `null` | Helper text. When set, an `<x-hint class="mt-1">` is rendered below the field. |
| `name` | `?string` | `null` | The `name` attribute shared by every radio input (the submitted field name). |
| `value` | `?string` | `null` | The currently selected star. The matching radio (`$i == $value`) gets `@checked`. |
| `stars` | `int` | `5` | Number of star inputs to render. |
| `size` | `string\|int` | `'24px'` | Star size. A numeric value is coerced to pixels (e.g. `32` → `32px`); a string is used verbatim. Emitted as the CSS variable `--star-size` on the container. |
| `icon` | `string` | `'fas fa-star'` | Icon class passed to `<x-icon>` for each star. |

### Derived data

- `required` (computed in `render()`): `true` when the component's `validation` attribute contains the string `required`. It is passed to the label as `:required`, so adding `validation="required"` marks the label accordingly.

## Slots

This component has no slots — content is driven entirely by props (`title`, `hint`, `icon`).

## Livewire / wire:model behavior

There is no built-in `wire:model` handling in the component. The radios share <span v-pre>`name="{{ $name }}"`</span> and use `@checked($i == $value)` for the initial selection, so binding is done the standard HTML way (form submission) or by attaching Livewire/Alpine attributes via the attribute bag on the tag.

## Usage

Basic 5-star rating with a label and a pre-selected value:

```blade
<x-rating
    name="quality"
    title="Service quality"
    :value="$review->quality"
/>
```

Custom star count, size, and icon, with a hint and required label:

```blade
<x-rating
    name="difficulty"
    title="Difficulty"
    hint="Pick from 1 to 10"
    :stars="10"
    :size="32"
    icon="fas fa-circle"
    validation="required"
/>
```

A heart rating with a fixed id:

```blade
<x-rating id="satisfaction" name="satisfaction" icon="fas fa-heart" />
```

## Rendered markup

For `<x-rating name="quality" :stars="3" :value="2" />` the output is roughly:

```html
<div class="rating-field" id="rating-…" style="--star-size: 24px;">
    <input type="radio" name="quality" id="rating-…-3" value="3" />
    <label for="rating-…-3"><i class="fas fa-star"></i></label>

    <input type="radio" name="quality" id="rating-…-2" value="2" checked />
    <label for="rating-…-2"><i class="fas fa-star"></i></label>

    <input type="radio" name="quality" id="rating-…-1" value="1" />
    <label for="rating-…-1"><i class="fas fa-star"></i></label>
</div>
```

## Gotchas

- Stars render in descending order; this is intentional and required for the standard CSS sibling-highlight technique. Do not assume DOM order matches visual left-to-right order.
- `value` is compared loosely (`$i == $value`), so pass an integer or numeric string for the selected star.
- `size` only coerces bare numbers to `px`; pass a full CSS length string (e.g. `'2rem'`) if you want other units.
- The `--star-size` CSS variable is set on the container, but the actual sizing/highlight styling lives in the dashboard's stylesheet for `.rating-field`.

## Related

- [Label component](/components/label)
- [Hint component](/components/hint)
- [Icon component](/components/icon)
