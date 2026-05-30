# Repeater Card

`<x-repeater-card>` is a ready-made card for a [Repeater](/components/repeater)
item. It gives each item a drag handle and insert/remove buttons that the
repeater wires up automatically, so you don't have to build that chrome yourself.
Use it as the root element of a repeater's item template.

## Usage

```blade
<x-repeater id="phones" name="phones" :title="__('Phone numbers')" :value="$phones">
    <x-repeater-card>
        <x-input name="label" :title="__('Label')" />
        <x-input name="number" :title="__('Number')" />
    </x-repeater-card>
</x-repeater>
```

Put the item's fields in the default slot; they render inside the card body. The
grip and the insert/remove buttons only work inside an `<x-repeater>`, which
clones the card per item.

## Options

This component has no options. Put the item fields in the default slot; any
`class` you pass is merged onto the card body.

## Examples

### Card body with layout classes

```blade
<x-repeater id="items" name="items">
    <x-repeater-card class="d-flex gap-2">
        <x-input name="title" />
        <x-select name="type" :options="$types" />
    </x-repeater-card>
</x-repeater>
```

## Related

- [Repeater](/components/repeater) — the container that clones this card per item.
- [Components overview](/components/overview) — using components and shared conventions.
