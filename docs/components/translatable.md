# Translatable

`<x-translatable>` renders the same form field once per configured locale, wrapped in Bootstrap tabs so a single multilingual attribute (e.g. a `title` stored via Spatie's `getTranslations()`) can be edited side by side. It delegates the actual field rendering to any other component (input, rich editor, etc.) through a dynamic component.

## What it is

A class-based component backed by `App\View\Components\Translatable` (`app/View/Components/Translatable.php`) and the view `resources/components/translatable.blade.php`. For each locale it builds a tab pane containing a `<x-dynamic-component>` whose `name` is suffixed with the locale (`name[en]`, `name[ar]`, …) and whose `value` is pulled from the per-locale value array. When more than one locale is configured it also renders a row of tab buttons.

## Props

All props come from the constructor of `App\View\Components\Translatable`:

| Prop | Type | Default | Purpose |
| --- | --- | --- | --- |
| `id` | `?string` | `uniqid('translatable-')` | Root element id; also the base for each tab/pane id (`{id}-{locale}`). |
| `name` | `?string` | `null` | Field name. Each locale field becomes `name[locale]`. If null, no name is emitted. |
| `validation` | `?string` | `null` | Validation rule string passed through to each locale's field. |
| `component` | `string` | `'input'` | Which Blade component to render per locale (resolved via `<x-dynamic-component>`). |
| `value` | `?array` | `[]` | Map of `locale => value`. Each locale field receives `value[locale]`. |
| `locales` | `array\|string\|null` | `null` | Locales to render. When null, falls back to `array_keys(config('app.locales'))`. A string is parsed as CSV via `parse_csv()`. |
| `validateLocales` | `array\|string\|null` | `null` | If set, only these locales keep the `validation` rule; others get `null`. A string is parsed as CSV. |
| `validateOnlyMainLocale` | `bool` | `false` | When true, only the first locale (index 0) keeps `validation`; the rest get `null`. |

Any extra HTML attributes (e.g. `type`, `:title`, `placeholder`) are forwarded via <span v-pre>`{{ $attributes }}`</span> to the inner per-locale component.

### Resolution behavior (`render()`)

- `id` defaults to a generated `translatable-*` id.
- `locales` falls back to the app locales and CSV strings are exploded.
- A null `value` is normalized to `[]`.
- Per locale, the component computes `localesConfig[locale]` with:
  - `id` = `{id}-{locale}`
  - `name` = `{name}[{locale}]` (or null)
  - `value` = `value[locale] ?? null`
  - `validation` = the rule, possibly nulled by `validateLocales` / `validateOnlyMainLocale`.

## Markup and slots

The root element carries the `translatable` attribute. The first locale pane/tab is marked `active`. Tab buttons are only rendered when `count($locales) > 1`. Each tab anchor carries `translatable-tab` and `locale="{locale}"` and uses Bootstrap's `data-bs-toggle="tab"`. There is no named slot; the field markup is produced by the `component` prop.

## Locale switching (JS)

There is no dedicated plugin. Locale switching is driven by Bootstrap tabs. The companion [`<x-translatable-switcher>`](/components/translatable) (view `resources/components/translatable-switcher.blade.php`) renders a globe dropdown that, on click, calls Bootstrap's jQuery tab API to switch every translatable field at once:

```js
$('[change-locale]').on('click', function() {
    const locale = $(this).attr('change-locale');

    $(`[translatable-tab][locale="${locale}"]`).tab('show');
});
```

So `change-locale="ar"` shows the `ar` tab on every `<x-translatable>` on the page.

## Examples

Editing a Spatie-translatable model, with an `input` and a `rich-editor` field (`resources/views/dashboard/static-pages/partials/form.blade.php`):

```blade
<x-translatable component="input" name="title"
    :value="$entry ? $entry->getTranslations('title') : old('title')"
    :title="__('Title')" validation="required" />

<x-translatable component="rich-editor" name="content"
    :value="$entry ? $entry->getTranslations('content') : old('content')"
    :title="__('Content')" />
```

A plain text input wired to a setting (`resources/views/dashboard/settings/partials/application-information.blade.php`):

```blade
<x-translatable component="input" type="text" name="app_name"
    :title="__('App name')" :value="setting('app_name')" validation="required" />
```

Adding the page-level switcher so users can flip every field's locale at once (`resources/views/dashboard/static-pages/edit.blade.php`):

```blade
<x-translatable-switcher />
```

## Gotchas

- `value` must be a `locale => value` map, not a scalar. Use `getTranslations(...)` (Spatie) or an array; a passed-in string is not split.
- Tab buttons only appear with two or more locales; a single locale still renders the field (active pane) but no tab row.
- `validateLocales` and `validateOnlyMainLocale` only adjust the `validation` rule per locale; they do not remove fields.
- The switcher relies on the Bootstrap jQuery `.tab('show')` API, so jQuery and Bootstrap's tab JS must be loaded.

## Related

- [translatable-switcher](/components/translatable) — page-wide locale switcher dropdown.
- Inner field components such as [Input](/components/input) and the rich editor are selected via the `component` prop.
