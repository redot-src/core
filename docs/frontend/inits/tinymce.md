# Tinymce Initializer

`tinymce` turns a textarea into a [TinyMCE](https://www.tiny.cloud/docs/) rich-text (WYSIWYG) editor with image upload support. It backs the [`<x-rich-editor>` component](/components/rich-editor) and follows the page's locale, text direction, and light/dark theme.

## Enable it

You rarely write `init="tinymce"` yourself — render the [`<x-rich-editor>` component](/components/rich-editor), which emits a textarea carrying the attribute and loads the vendor script:

```blade
<x-rich-editor name="content" :title="__('Content')" :value="old('content', $entry?->content)" />
```

For a translatable editor, wrap it:

```blade
<x-translatable component="rich-editor" name="content" :title="__('Content')"
    :value="$entry ? $entry->getTranslations('content') : old('content')" />
```

See [Asset & Init System](/frontend/asset-system) for how the `init` attribute is wired.

## Options

The toolbar, plugins, and height come preconfigured for dashboard content, and the editor reads its language, direction, and skin from the page — so there is little to set per field. Images pasted or picked in the editor upload automatically through the dashboard's upload endpoint.

The field must have an `id` (the component generates one if you omit it). When the user toggles light/dark, the editor rebuilds to pick up the new skin.

## Related

- [Rich Editor component](/components/rich-editor) — the component that enables this for you.
- [Theming](/frontend/theming) — the light/dark mode the editor follows.
- [Asset & Init System](/frontend/asset-system) — the `init` attribute and how widgets are wired.
