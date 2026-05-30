# Google Analytics

Injects the Google Analytics 4 (gtag.js) tracking snippet into the website. It's
already wired into the website layout — you just turn it on with a setting.

## Usage

Set the `google_analytics_property_id` setting to your GA4 Measurement ID. While
it's empty, nothing is emitted; once it has a value, the tracking snippet loads
on every page. There is no tag to place yourself — see
[Settings](/foundation/settings) for the `setting()` helper and where these
values are edited.

## Options

- **`google_analytics_property_id`** (setting) — your GA4 Measurement ID (e.g.
  `G-XXXXXXXXXX`). Leave empty to disable tracking entirely.

## Related

- [Settings](/foundation/settings) — the `setting()` helper and settings screen.
- [Facebook Pixel](/components/facebook-pixel) — its sibling tracking integration.
