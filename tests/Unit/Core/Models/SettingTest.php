<?php

use Redot\Models\Setting;

beforeEach(function () {
    Setting::define('app_name')
        ->array()
        ->rules([
            'app_name' => ['required', 'array'],
            'app_name.*' => ['required', 'string'],
        ])
        ->default(['en' => 'Nexus', 'ar' => 'نيكسس']);

    Setting::define('website_locales')
        ->array()
        ->rules(['required', 'array', 'min:1'])
        ->default(['en', 'ar']);

    Setting::define('page_loader_enabled')
        ->boolean()
        ->default(false);

    Setting::define('theme')
        ->array()
        ->default(['primary' => 'blue', 'radius' => 1]);
});

it('falls back to the defined schema default when the setting is not persisted', function () {
    expect(Setting::get('page_loader_enabled'))->toBeFalse()
        ->and(Setting::get('items_per_page', 25))->toBe(25)
        ->and(Setting::get('theme.primary'))->toBe('blue');
});

it('does not cache caller defaults for settings that are not persisted', function () {
    expect(Setting::get('items_per_page', 25))->toBe(25)
        ->and(Setting::get('items_per_page', 50))->toBe(50)
        ->and(cache()->has('settings.items_per_page'))->toBeFalse();
});

it('continues to cache persisted setting values', function () {
    Setting::set('items_per_page', 25);

    expect(Setting::get('items_per_page'))->toBe(25);

    Setting::where('key', 'items_per_page')->update(['value' => 50]);

    expect(Setting::get('items_per_page'))->toBe(25)
        ->and(Setting::get('items_per_page', fresh: true))->toBe(50);
});

it('returns the defined default for a key including nested dot paths', function () {
    expect(Setting::default('theme.primary'))->toBe('blue')
        ->and(Setting::default('app_name.en'))->toBe('Nexus')
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

it('invalidates the cache when the setting record is deleted', function () {
    Setting::set('page_loader_enabled', true);

    expect(Setting::get('page_loader_enabled'))->toBeTrue();

    Setting::where('key', 'page_loader_enabled')->first()->delete();

    expect(Setting::get('page_loader_enabled'))->toBeFalse();
});

it('exposes the validation rules declared per setting and per nested path', function () {
    $rules = Setting::rules();

    expect($rules)->toHaveKey('app_name')
        ->and($rules)->toHaveKey('app_name.*')
        ->and($rules)->toHaveKey('website_locales')
        ->and($rules['website_locales'])->toContain('required');
});

it('registers fluent setting types and exposes their schema metadata', function () {
    $logo = Setting::define('logo')->file()->rules(['nullable'])->default('logo.svg');
    Setting::define('enabled')->boolean();
    Setting::define('title')->string();
    Setting::define('limit')->integer();
    Setting::define('ratio')->float();
    Setting::define('options')->array();
    Setting::define('custom')->type('color');

    expect(Setting::type('logo'))->toBe('file')
        ->and(Setting::type('enabled'))->toBe('boolean')
        ->and(Setting::type('title'))->toBe('string')
        ->and(Setting::type('limit'))->toBe('integer')
        ->and(Setting::type('ratio'))->toBe('float')
        ->and(Setting::type('options'))->toBe('array')
        ->and(Setting::type('custom'))->toBe('color')
        ->and(Setting::type('theme.primary'))->toBe('array')
        ->and(Setting::type('missing'))->toBeNull()
        ->and($logo->getType())->toBe('file')
        ->and($logo->getRules())->toBe(['nullable'])
        ->and($logo->getDefault())->toBe('logo.svg')
        ->and(Setting::schema()['logo'])->toBe([
            'type' => 'file',
            'rules' => ['nullable'],
            'default' => 'logo.svg',
        ]);
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
