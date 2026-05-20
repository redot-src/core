<?php

use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Redot\RedotServiceProvider;

it('merges the package configuration without overriding host overrides', function () {
    config()->set('redot.features.dashboard.prefix', 'admin');

    app()->register(RedotServiceProvider::class, force: true);

    expect(config('redot.features.dashboard.prefix'))->toBe('admin')
        ->and(config('redot.features.dashboard.enabled'))->toBeTrue()
        ->and(config('redot.locales'))->toHaveCount(2);
});

it('uses the package pagination view as the paginator default', function () {
    expect(Paginator::$defaultView)->toBe('components.pagination');
});

it('compiles the themer Blade directive into a script tag with the configured theme', function () {
    $compiled = Blade::compileString('@themer(custom)');

    expect($compiled)
        ->toContain("window.themerKey = 'custom'")
        ->toContain('window.themeConfig =')
        ->toContain('themer.js');
});

it('encodes arrays through the configured JSON cast without escaping slashes', function () {
    $value = Json::encode(['url' => 'https://example.test/path']);

    expect($value)->toBe('{"url":"https://example.test/path"}');
});
