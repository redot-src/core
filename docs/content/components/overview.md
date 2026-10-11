# Components Overview

Redot Dashboard ships a library of Blade components for building admin and website screens — form inputs, layout primitives, display widgets, and third-party integrations. This page covers how to use them and the conventions every component shares; each component has its own page for the details.

## Using a component

Components are plain Blade tags. Pass props as attributes, and prefix any value that is a PHP expression with `:`.

```blade
<x-input name="title" :title="__('Title')" :value="$post?->title" validation="required" />
```

- Plain attributes pass a literal string: `type="email"`.
- `:` attributes evaluate PHP: `:value="$post->title"`, `:title="__('Name')"`.
- Any extra HTML attribute you add (`id`, `class`, `placeholder`, `disabled`, `readonly`, `wire:model`, …) is forwarded to the underlying element, so you can treat a component like the input it wraps.

## Shared form-field conventions

Most form inputs (`<x-input>`, `<x-select>`, `<x-textarea>`, `<x-date-picker>`, and friends) accept the same core attributes, so once you learn one you know them all:

- **`name`** — the field name submitted with the form.
- **`title`** — label text shown above the field (a localized string).
- **`value`** — the initial value, such as the model's current attribute.
- **`hint`** — small helper text shown beneath the field.
- **`validation`** — client-side validation rules (e.g. `required`, `required|email`). If the rules include `required`, the label shows a required marker. See [RedotValidator](/frontend/plugins/redot-validator).

After a failed validation redirect, form fields refill themselves from the submitted input, so you don't need to wrap `value` in `old()`. This works for array names too (`tags[]`, `title[en]`).

To give a custom control the same label, hint, and required marker, wrap it in [`<x-field>`](/components/field).

Components that wrap a JavaScript widget (date pickers, selects, the rich editor, uploader, …) light up automatically through the [asset & init system](/frontend/asset-system) — you don't wire up any JS yourself.

## Component index

### Form inputs

- [Input](/components/input) — `<x-input>`
- [Textarea](/components/textarea) — `<x-textarea>`
- [Select](/components/select) — `<x-select>`
- [Select Remote](/components/select-remote) — `<x-select-remote>`
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
- [Field](/components/field) — `<x-field>`, the label + hint frame for custom controls
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
- [Apex Chart](/components/apex-chart) — `<x-apex-chart>`
- [Avatar](/components/avatar) — `<x-avatar>`
- [Status](/components/status) — flash-message alert
- [Icon](/components/icon) — `<x-icon>`
- [Flag](/components/flag) / [Countries](/components/countries)
- [Logo](/components/logo), [Favicon](/components/favicon), [Social Icon](/components/social-icon)
- [Toasts](/components/toasts) — renders flashed toast notifications

### Integrations

- [Facebook Pixel](/components/facebook-pixel)
- [Google Analytics](/components/google-analytics)
- [Captcha](/components/captcha) — Cloudflare Turnstile

## Related

- [Asset & Init System](/frontend/asset-system) — how JS-backed components initialize.
- [RedotValidator](/frontend/plugins/redot-validator) — the `validation` attribute.
- [Layouts](/layouts/overview) — the page shells components live inside.
