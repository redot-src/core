# HugeRTE Initializer

`hugerte` turns a textarea into a [HugeRTE](https://github.com/hugerte/hugerte) rich-text (WYSIWYG) editor with image upload support. It backs the [`<x-rich-editor>` component](/components/rich-editor) and follows the page's locale, text direction, and light/dark theme.

## Enable it

You rarely write `init="hugerte"` yourself — render the [`<x-rich-editor>` component](/components/rich-editor), which emits a textarea carrying the attribute and loads the vendor script:

```blade
<x-rich-editor name="body" :title="__('Body')" :value="$post?->body" />
```

For a translatable editor, wrap it:

```blade
<x-translatable component="rich-editor" name="body" :title="__('Body')"
    :value="$post?->getTranslations('body')" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

The toolbar, plugins, and height come preconfigured, and the editor reads its language, direction, and skin from the page — so there is little to set per field. Images pasted or picked in the editor upload automatically to the URL in the field's `hugerte-upload-url` attribute, which `<x-rich-editor>` fills in from the `global.hugerte.upload` route. Without that attribute (for example when `redot.components.hugerte.enabled` is off), image uploads are turned off.

The field must have an `id` (the component generates one if you omit it). When the user toggles light/dark, the editor rebuilds to pick up the new skin.

## Related

- [Rich Editor component](/components/rich-editor) — the component that enables this for you.
- [Theming](/frontend/theming) — the light/dark mode the editor follows.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
