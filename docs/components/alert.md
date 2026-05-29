# Alert

`<x-alert>` renders a contextual Bootstrap-style alert box with an optional icon, title, description, and dismiss button. It is backed by the `App\View\Components\Alert` class, which maps a semantic `type` to a Bootstrap alert variant and a default Font Awesome icon.

## What it is

The component combines a class (`app/View/Components/Alert.php`) with a view (`resources/components/alert.blade.php`). The class resolves the alert's CSS classes and default icon, then the view renders the markup:

```html
<div role="alert" class="alert alert-success ...">
    <x-icon icon="..." class="alert-icon icon" />  {{-- when an icon is set --}}
    <div>...title / description / slot...</div>
    <a class="btn-close" data-bs-dismiss="alert" ...></a>  {{-- when dismissible --}}
</div>
```

The root element always carries `role="alert"`, the `alert` class, and an `alert-{variant}` class. When `dismissible` is true it also gets `alert-dismissible` and renders a close link wired to Bootstrap's `data-bs-dismiss="alert"`.

## Props

All props come from the `Alert` constructor:

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `icon` | `?string` | `null` | Icon name passed to `<x-icon>`. If left null, it defaults to the icon mapped from `type`. Pass `:icon="false"` to suppress the icon entirely. |
| `title` | `?string` | `null` | Heading text. Rendered as `<h4 class="alert-title">` when `description` is also set; otherwise rendered as the alert body (falling back to the slot when empty). |
| `description` | `?string` | `null` | Secondary text rendered inside `<div class="text-secondary">`. When present, `title` is promoted to a bold title above it. |
| `type` | `string` | `'success'` | Semantic variant. Accepts `success`, `error`, `warning`, `info`. Any unrecognized value falls back to `success`. |
| `dismissible` | `bool` | `false` | When true, adds `alert-dismissible` and a close button. |

### Type to variant and icon mapping

The `type` prop is normalized in `render()`. Unknown values fall back to `success`, and `error` maps to the Bootstrap `danger` variant. When no `icon` is given, the default icon is taken from the type:

| `type` | CSS variant class | Default icon |
| --- | --- | --- |
| `success` | `alert-success` | `fas fa-check-circle` |
| `error` | `alert-danger` | `fas fa-exclamation-circle` |
| `warning` | `alert-warning` | `fas fa-exclamation-triangle` |
| `info` | `alert-info` | `fas fa-info-circle` |

## Slots and attributes

- **Default slot** — used as the body when `title` is empty and `description` is not set (<span v-pre>`{{ $title ?: $slot }}`</span>). This is the most common form: rich markup (lists, paragraphs) passed between the tags.
- **Attribute bag** — any extra attributes (e.g. `class`, `id`) are merged onto the root `<div>` via <span v-pre>`{{ $attributes }}`</span>. The component appends its computed `alert`/`alert-{variant}`/`alert-dismissible` classes through the attribute bag, so user-supplied classes are merged rather than overwritten.

## Body rendering logic

The body resolves in this order:

1. If `description` is set: render `title` as a bold `<h4 class="alert-title">` plus `description` as secondary text.
2. Otherwise: render `title`, or fall back to the default slot when `title` is empty.

So `title` + `description` produces a titled alert, while a bare slot produces a plain alert body.

## Usage

Plain informational alert with slot content (the icon defaults to `fas fa-info-circle`):

```blade
<x-alert type="info">
    <p>{{ __('This token has been modified. The original translation was') }}:</p>
    <strong>"{{ $token->original_translation }}"</strong>
</x-alert>
```

Iconless info alert wrapping a list, suppressing the default icon with `:icon="false"`:

```blade
<x-alert type="info" :icon="false">
    <ul class="m-0">
        <li>{{ __('There\'s about :count token(s) that need to be published.', ['count' => $count]) }}</li>
    </ul>
</x-alert>
```

Dismissible alert driven from a session flash (this is how the related `<x-status>` component is built):

```blade
<x-alert :type="$status" :dismissible="true" {{ $attributes }}>
    {{ session($status) }}
</x-alert>
```

Titled alert with a description:

```blade
<x-alert type="success" title="Saved" description="Your changes have been stored." />
```

## Gotchas

- **Suppressing the icon:** because `icon` defaults to the type's icon, you must pass `:icon="false"` (or `:icon="null"` will *not* work — null triggers the default). Use the boolean `false` to render no icon.
- **`error` vs `danger`:** use `type="error"` (the semantic value); it is translated to the `alert-danger` Bootstrap class internally.
- **Dismiss behavior** relies on Bootstrap's `data-bs-dismiss="alert"`, so Bootstrap's alert JS must be loaded for the close button to work; there is no custom JS init.
- **Icon dependency:** the icon is rendered through `<x-icon>` with Font Awesome class names, so Font Awesome must be available.

## Related

- [Status component](/components/status) — a thin wrapper around `<x-alert>` that renders dismissible alerts from session flash data.
- [Icon component](/components/icon) — used internally to render the alert icon.
