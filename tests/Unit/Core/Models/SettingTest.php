<?php

use Redot\Models\Setting;

it('falls back to the configured schema default when the setting is not persisted', function () {
    expect(Setting::get('page_loader_enabled'))->toBeFalse()
        ->and(Setting::get('items_per_page', 25))->toBe(25)
        ->and(Setting::get('theme.primary'))->toBe('blue');
});

it('returns the configured default for a key including nested dot paths', function () {
    expect(Setting::default('theme.primary'))->toBe('blue')
        ->and(Setting::default('app_name.en'))->toBe('Dashboard')
        ->and(Setting::default('missing'))->toBeNull();
});

it('persists and round-trips scalars, booleans, and arrays through the Union cast', function () {
    Setting::set('page_loader_enabled', true);
    Setting::set('items_per_page', 25);
    Setting::set('theme', ['primary' => 'red', 'radius' => 2]);

    expect(Setting::get('page_loader_enabled', fresh: true))->toBeTrue()
        ->and(Setting::get('items_per_page', fresh: true))->toBe(25)
        ->and(Setting::get('theme', fresh: true))->toBe(['primary' => 'red', 'radius' => 2])
        ->and(Setting::get('theme.primary', fresh: true))->toBe('red');
});

it('invalidates the top-level cache when the setting record changes', function () {
    Setting::set('app_name', ['en' => 'Old']);

    expect(Setting::get('app_name.en'))->toBe('Old');

    Setting::where('key', 'app_name')->first()->update(['value' => ['en' => 'New']]);

    expect(Setting::get('app_name.en'))->toBe('New');
});

it('exposes the validation rules declared per setting and per nested path', function () {
    $rules = Setting::rules();

    expect($rules)->toHaveKey('app_name')
        ->and($rules)->toHaveKey('app_name.*')
        ->and($rules)->toHaveKey('website_locales')
        ->and($rules['website_locales'])->toContain('required');
});

it('returns the full settings collection as a key-value map when no key is provided', function () {
    Setting::set('foo', 'bar');
    Setting::set('items_per_page', 42);

    $all = setting();

    expect($all)->toMatchArray([
        'foo' => 'bar',
        'items_per_page' => 42,
    ]);
});
