# Google Analytics

A zero-prop Blade partial that injects the Google Analytics 4 (gtag.js) tracking snippet into the page. It renders only when a property ID has been configured in settings, so it is safe to include unconditionally in your layouts.

## What it is

`resources/components/google-analytics.blade.php` is an **include-style Blade partial** — there is no backing PHP class in `app/View/Components`, so it is not used as an `<x-google-analytics />` tag. Instead it is pulled in with `@include('components.google-analytics')`.

It wraps the official **Google `gtag.js`** loader from `https://www.googletagmanager.com/gtag/js`. The entire output is conditional on the `google_analytics_property_id` setting and is pushed onto the `scripts` stack rather than rendered inline.

## Source

```blade
@pushIf(setting('google_analytics_property_id'), 'scripts')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ setting('google_analytics_property_id') }}"></script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
        gtag('config', '{{ setting('google_analytics_property_id') }}');
    </script>
@endPushIf
```

## Props / attributes

The partial accepts **no props, slots, or attributes**. Its only input is the application setting:

| Setting | Default | Purpose |
| --- | --- | --- |
| `google_analytics_property_id` | `''` (empty) | The GA4 Measurement / property ID (e.g. `G-XXXXXXXXXX`). Used both as the `gtag/js?id=` query param and in the `gtag('config', …)` call. |

The default is declared in the consumer app's `config/redot.php` settings block:

```php
'google_analytics_property_id' => [
    'default' => '',
],
```

Because the default is an empty string, `@pushIf(setting('google_analytics_property_id'), 'scripts')` evaluates falsy and **nothing is emitted until an ID is set**.

## How the output is delivered

The snippet is wrapped in `@pushIf(..., 'scripts')` / `@endPushIf`. Both the async loader and the inline `gtag()` bootstrap are appended to the `scripts` stack. That stack is rendered once via `@stack('scripts')` in `resources/layouts/scaffold.blade.php`:

```blade
@stack('scripts')
```

So the partial does not produce markup at its include location — it queues script tags to be flushed wherever the `scripts` stack is output.

## Usage

It is included in the public website base layout, alongside the Facebook Pixel partial:

```blade
{{-- resources/layouts/website/base.blade.php --}}
@include('components.google-analytics')
@include('components.facebook-pixel')
```

You generally do not call this yourself — it ships in the website layout. To add it to another layout, include it inside (or before) the markup that renders the `scripts` stack:

```blade
@include('components.google-analytics')
```

## Configuring the property ID

The ID is managed from the dashboard's third-party services settings screen, where it is edited with an `<x-input>` bound to the `google_analytics_property_id` setting:

```blade
{{-- resources/views/dashboard/settings/partials/3rd-party-services.blade.php --}}
<x-input name="google_analytics_property_id" :title="__('Google Analytics property ID')"
    value="{{ setting('google_analytics_property_id') }}" />
```

Saving a value here flips the partial on for every page that renders the `scripts` stack.

## Notes and gotchas

- **No `<x-google-analytics />` tag.** There is no component class; use `@include('components.google-analytics')`. Calling it as an `x-` component will fail.
- **Requires the `scripts` stack.** If your layout never renders `@stack('scripts')`, the tracking code is silently dropped.
- **All-or-nothing.** With an empty `google_analytics_property_id` the partial emits no script at all — there is no partial/degraded mode.
- **No Livewire / `wire:model`.** The partial holds no reactive state; the ID is read once at render time via `setting()`.
- The `dataLayer` and `gtag()` globals it registers are the standard Google Analytics globals, not Redot-specific.

## Related

- [Input component](/components/input) — used on the settings screen to edit the property ID.
- Facebook Pixel partial (`@include('components.facebook-pixel')`) — its sibling third-party tracking include.
