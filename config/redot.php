<?php

return [

    /*--------------------------------------------------------------------------
    | Redot Features
    |--------------------------------------------------------------------------
    |
    | The features that are enabled for the application, You can enable or
    | disable features as per your requirements.
    |
    */

    'features' => [
        'website-api' => [
            'enabled' => true,
        ],

        'dashboard-api' => [
            'enabled' => true,
            'prefix' => 'dashboard',
        ],

        'website' => [
            'enabled' => true,
        ],

        'dashboard' => [
            'enabled' => true,
            'prefix' => 'dashboard',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | The list of available locales for the website and dashboard.
    |
    */

    'locales' => [
        [
            'code' => 'en',
            'name' => 'English',
            'is_rtl' => false,
        ],

        [
            'code' => 'ar',
            'name' => 'العربية',
            'is_rtl' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Append Locale to URL
    |--------------------------------------------------------------------------
    |
    | This option determines if the locale should be appended to the URL.
    |
    */

    'append_locale_to_url' => true,

    /*
    |--------------------------------------------------------------------------
    | Redirect non-localized URLs
    |--------------------------------------------------------------------------
    |
    | This option determines if the non-localized URLs should be redirected to
    | same URL with the default locale.
    |
    */

    'redirect_non_locale_urls' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | The default settings for the application.
    |
    */

    'default_settings' => [
        'app_logo_dark' => 'assets/images/logo-dark.svg',
        'app_logo_light' => 'assets/images/logo-light.svg',
        'app_name' => [
            'en' => 'Dashboard',
            'ar' => 'لوحة التحكم',
        ],
        'website_locales' => ['en', 'ar'],
        'dashboard_locales' => ['en', 'ar'],
        'page_loader_enabled' => false,
        'service_worker_enabled' => true,
        'facebook_pixel_id' => '',
        'google_analytics_property_id' => '',
        'cloudflare_turnstile_site_key' => '',
        'cloudflare_turnstile_secret_key' => '',
        'head_code' => '',
        'body_code' => '',
        'dashboard_sidebar_theme' => 'inherit',

        'theme' => [
            'primary' => 'blue',
            'base' => 'default',
            'font' => 'sans-serif',
            'radius' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings Validations
    |--------------------------------------------------------------------------
    |
    | The default settings validations.
    |
    */

    'default_settings_validations' => [
        'app_name' => 'required|array',
        'app_name.*' => 'required|string',
        'website_locales' => 'required|array|min:1',
        'dashboard_locales' => 'required|array|min:1',
    ],
];
