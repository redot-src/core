<?php

use Redot\Models\Setting;

it('exposes the configured settings schema defaults and rules', function () {
    expect(Setting::schema())->toHaveKey('app_name')
        ->and(Setting::defaults())->toMatchArray([
            'app_logo_dark' => 'assets/images/logo-dark.svg',
            'website_locales' => ['en', 'ar'],
            'service_worker_enabled' => true,
        ])
        ->and(Setting::rules())->toHaveKey('app_name')
        ->and(Setting::rules())->toHaveKey('app_name.*')
        ->and(Setting::rules())->toHaveKey('website_locales');
});

it('returns configured defaults including nested values', function () {
    expect(Setting::default('theme.primary'))->toBe('blue')
        ->and(Setting::default('app_name.en'))->toBe('Dashboard')
        ->and(Setting::default('missing'))->toBeNull();
});

it('persists and retrieves scalar boolean numeric and array values', function () {
    Setting::set('page_loader_enabled', true);
    Setting::set('items_per_page', 25);
    Setting::set('theme', ['primary' => 'red', 'radius' => 2]);

    expect(Setting::get('page_loader_enabled', fresh: true))->toBeTrue()
        ->and(Setting::get('items_per_page', fresh: true))->toBe(25)
        ->and(Setting::get('theme', fresh: true))->toBe(['primary' => 'red', 'radius' => 2])
        ->and(Setting::get('theme.primary', fresh: true))->toBe('red')
        ->and(Setting::get('theme.radius', fresh: true))->toBe(2);
});

it('invalidates cached settings when a setting changes', function () {
    Setting::set('app_name', ['en' => 'Old']);

    expect(Setting::get('app_name.en', fresh: true))->toBe('Old');

    Setting::where('key', 'app_name')->first()->update(['value' => ['en' => 'New']]);

    expect(Setting::get('app_name.en'))->toBe('New');
});
