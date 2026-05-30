# Facebook Pixel

Injects Meta's Pixel tracking code into the website layout and tracks a
`PageView` on every render. It's already wired into the website layout — you
just turn it on with a setting.

## Usage

Set the `facebook_pixel_id` setting to your Meta Pixel ID. While it's empty,
nothing is emitted; once it has a value, the Pixel code loads on every page.
There is no tag to place yourself — see [Settings](/foundation/settings) for the
`setting()` helper and where these values are edited.

## Options

- **`facebook_pixel_id`** (setting) — your Meta Pixel ID. Leave empty to disable
  tracking entirely.

## Related

- [Settings](/foundation/settings) — the `setting()` helper and settings screen.
- [Google Analytics](/components/google-analytics) — its sibling tracking integration.
