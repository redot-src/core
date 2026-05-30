# Page Loader

`<x-page-loader>` shows a full-screen splash overlay — the app logo and a
spinner — while the page boots, then fades itself out once everything has
loaded. It takes no attributes.

## Usage

```blade
<x-page-loader />
```

In the shipped dashboard layout the loader is gated behind the
`page_loader_enabled` setting, so it only appears when that setting is on — see
[Settings](/foundation/settings).

## Related

- [Logo](/components/logo) — rendered inside the loader.
- [Settings](/foundation/settings) — the `page_loader_enabled` setting.
