# Logo

`<x-logo>` renders the application logo. It automatically swaps between the
light- and dark-theme versions to match the active theme.

## Usage

```blade
<x-logo />
```

The logo images come from the `app_logo_dark` and `app_logo_light` settings —
see [Settings](/foundation/settings).

## Options

- **`lazy`** — defer loading of the images (`true` by default). Set
  `:lazy="false"` for above-the-fold or splash contexts where the logo must
  appear immediately.

Extra classes you pass apply to the logo.

## Examples

### Eager-loaded logo with a margin

```blade
<x-logo class="mb-4" :lazy="false" />
```

## Related

- [Page Loader](/components/page-loader) — shows the logo on a splash screen.
- [Settings](/foundation/settings) — where the logo images are configured.
