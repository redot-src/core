@pushOnce('meta', 'cloudflare-turnstile-meta')
    <meta name="cloudflare-turnstile-site-key" content="{{ setting('cloudflare_turnstile_site_key') }}">
@endPushOnce

@pushOnce('plugins-scripts', 'cloudflare-turnstile-scripts')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
@endPushOnce
