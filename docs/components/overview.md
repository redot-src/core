# Components Overview

Redot Dashboard ships a library of Blade components for building admin and
website screens — form inputs, layout primitives, display widgets, and
third-party integrations. This page covers how to use them and the conventions
every component shares; each component has its own page for the details.

## Using a component

Components are plain Blade tags. Pass props as attributes, and prefix any value
that is a PHP expression with `:`.

```blade
<x-input name="title" :title="__('Title')" :value="old('title', $post?->title)" validation="required" />
```

- Plain attributes pass a literal string: `type="email"`.
- `:` attributes evaluate PHP: `:value="$post->title"`, `:title="__('Name')"`.
- Any extra HTML attribute you add (`id`, `class`, `placeholder`, `disabled`,
  `readonly`, `wire:model`, …) is forwarded to the underlying element, so you can
  treat a component like the input it wraps.

## Shared form-field conventions

Most form inputs (`<x-input>`, `<x-select>`, `<x-textarea>`, `<x-date-picker>`,
and friends) accept the same core attributes, so once you learn one you know them
all:

- **`name`** — the field name submitted with the form.
- **`title`** — label text shown above the field (a localized string).
- **`value`** — the initial value; pair with `old()` to preserve input on
  validation errors.
- **`hint`** — small helper text shown beneath the field.
- **`validation`** — client-side validation rules (e.g. `required`,
  `required|email`). If the rules contain `required`, the label shows a required
  marker. See [RedotValidator](/frontend/plugins/redot-validator).

Components that wrap a JavaScript widget (date pickers, selects, the rich editor,
uploader, …) light up automatically through the [asset & init
system](/frontend/asset-system) — you don't wire up any JS yourself.

## Component index

### Form inputs

- [Input](/components/input) — `<x-input>`
- [Textarea](/components/textarea) — `<x-textarea>`
- [Select](/components/select) — `<x-select>`
- [Checkboxes](/components/checkboxes) — `<x-checkboxes>`
- [Radios](/components/radios) / [Radios Colored](/components/radios-colored)
- [Toggle](/components/toggle) — `<x-toggle>`
- [Date Picker](/components/date-picker) — `<x-date-picker>`
- [Color Picker](/components/color-picker) — `<x-color-picker>`
- [Icon Picker](/components/icon-picker) — `<x-icon-picker>`
- [Rich Editor](/components/rich-editor) — `<x-rich-editor>`
- [Rating](/components/rating) — `<x-rating>`
- [Uploader](/components/uploader) — `<x-uploader>`
- [Attachments](/components/attachments) — `<x-attachments>`
- [Translatable](/components/translatable) / [Translatable Switcher](/components/translatable-switcher)
- [Query Builder](/components/query-builder) — `<x-query-builder>`
- [Repeater](/components/repeater) / [Repeater Card](/components/repeater-card)
- [Captcha](/components/captcha) — `<x-captcha>`
- [Label](/components/label), [Hint](/components/hint), [File Hint](/components/file-hint)

### Layout & structure

- [Form](/components/form) — `<x-form>`
- [Form Card](/components/form-card) — `<x-form-card>`
- [Page Header](/components/page-header), [Page Loader](/components/page-loader)
- [Pagination](/components/pagination)
- [Empty](/components/empty) — empty-state placeholder
- [Layouts](/layouts/overview) — page shells (`<x-layouts::dashboard>`, …)

### Display

- [Alert](/components/alert) — `<x-alert>`
- [Avatar](/components/avatar) — `<x-avatar>`
- [Status](/components/status) — flash-message alert
- [Icon](/components/icon) — `<x-icon>`
- [Flag](/components/flag) / [Countries](/components/countries)
- [Logo](/components/logo), [Social Icon](/components/social-icon)

### Integrations

- [Facebook Pixel](/components/facebook-pixel)
- [Google Analytics](/components/google-analytics)
- [Captcha](/components/captcha) — Cloudflare Turnstile

## Related

- [Asset & Init System](/frontend/asset-system) — how JS-backed components initialize.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
- [Layouts](/layouts/overview) — the page shells components live inside.
