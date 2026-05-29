# Translatable Switcher

`<x-translatable-switcher>` is an anonymous Blade component that renders a Bootstrap dropdown of the app's configured locales. Clicking a locale activates the matching translation tab rendered by the [`<x-translatable>`](/components/translatable) component, letting editors switch which language's fields are visible on a form.

## What it is

The component lives at `resources/components/translatable-switcher.blade.php`. There is no backing PHP class in `app/View/Components` — it is a pure anonymous component declared with `@props`. It outputs a globe-icon dropdown button labeled "Change locale" and a menu of locale items, then pushes a small jQuery script that wires each menu item to the corresponding translatable tab.

## Props

Both props are optional and declared via `@props`:

| Prop | Default | Description |
| --- | --- | --- |
| `id` | `uniqid('translatable-')` | The `id` attribute of the root `<div>`. A unique value is generated per render if omitted. |
| `locales` | `array_keys(config('app.locales'))` | Array of locale keys to list in the dropdown. Defaults to every locale configured under `app.locales`. |

The display label for each locale comes from `config("app.locales.$locale")`, shown alongside the raw key in parentheses, e.g. `English (en)`.

## Attributes

Any extra attributes are forwarded to the root element. The component always merges a `translatable-switcher` marker attribute and adds the Bootstrap `dropdown` class:

```blade
<div id="{{ $id }}" {{ $attributes->class(['dropdown'])->merge(['translatable-switcher' => true]) }}>
```

So additional classes you pass are appended to `dropdown`, and other attributes pass through.

## Markup and behavior

The rendered structure is:

- A `<button class="btn dropdown-toggle" data-bs-toggle="dropdown">` with a `fa-globe` icon and the translated "Change locale" text.
- A `.dropdown-menu` containing one <span v-pre>`<button class="dropdown-item" change-locale="{{ $locale }}">`</span> per locale.

The pushed script (pushed once into the `scripts` stack) binds click handlers using jQuery:

```js
$('[change-locale]').on('click', function() {
    const locale = $(this).attr('change-locale');

    $(`[translatable-tab][locale="${locale}"]`).tab('show');
});
```

When a menu item is clicked it reads its `change-locale` value and calls Bootstrap's `.tab('show')` on the element matching `[translatable-tab][locale="<locale>"]`. Those targets are produced by the [`<x-translatable>`](/components/translatable) component, which renders each locale's tab anchor with both `translatable-tab` and <span v-pre>`locale="{{ $locale }}"`</span> attributes.

### Gotchas

- The switcher only does something when a `<x-translatable>` field group is present on the same page; the script targets that component's `[translatable-tab]` anchors.
- The script relies on jQuery and Bootstrap's tab plugin (`.tab('show')`) being loaded globally.
- The script is registered with `@pushOnce('scripts')`, so it is emitted only once even if multiple switchers appear on a page; a single set of handlers governs all `[change-locale]` buttons.
- `change-locale`, `translatable-tab`, and `locale` are bespoke attribute hooks (not `data-*`).

## Usage

Real usage in the dashboard places the switcher in the header slot of a form card, above translatable fields. From `resources/views/dashboard/static-pages/create.blade.php` (the edit page is identical):

```blade
<x-layouts::dashboard>
    <x-form-card resource="static-pages">
        <x-slot:header>
            <x-translatable-switcher />
        </x-slot:header>
    </x-form-card>
</x-layouts::dashboard>
```

Restricting the listed locales:

```blade
<x-translatable-switcher :locales="['en', 'ar']" />
```

Passing a fixed id and extra class:

```blade
<x-translatable-switcher id="page-locales" class="dropdown-end" />
```

## Related

- [Translatable component](/components/translatable) — renders the per-locale tab panes and the `[translatable-tab]` anchors this switcher activates.
