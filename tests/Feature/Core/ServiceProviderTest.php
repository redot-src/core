<?php

use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Redot\RedotServiceProvider;

it('merges the package configuration without overriding host overrides', function () {
    config()->set('redot.features.dashboard.prefix', 'admin');

    app()->register(RedotServiceProvider::class, force: true);

    expect(config('redot.features.dashboard.prefix'))->toBe('admin')
        ->and(config('redot.features.dashboard.enabled'))->toBeTrue()
        ->and(config('redot.locales'))->toHaveCount(2);
});

it('registers layout components from the configured path and namespace', function () {
    app()->setBasePath(dirname(__DIR__, 3));
    config()->set('redot.paths.layouts', [
        'path' => 'tests/Fixtures/layouts',
        'namespace' => 'Tests\\Fixtures\\Layouts',
    ]);

    app()->register(RedotServiceProvider::class, force: true);

    expect(Blade::render('<x-layouts::fixture />'))->toContain('fixture layout')
        ->and(Blade::getClassComponentNamespaces())->toHaveKey('layouts', 'Tests\\Fixtures\\Layouts');
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

it('resolves the themer script URL when the view renders instead of when it compiles', function () {
    URL::forceRootUrl('http://compiled.test');
    $compiled = Blade::compileString("@themer(custom)@stack('pre-content')");

    URL::forceRootUrl('http://rendered.test');
    $__env = app('view');
    ob_start();
    eval('?>' . $compiled);
    $html = ob_get_clean();

    expect($html)
        ->toContain('http://rendered.test/vendor/redot/js/themer.js')
        ->not->toContain('compiled.test');
});

it('encodes arrays through the configured JSON cast without escaping slashes', function () {
    $value = Json::encode(['url' => 'https://example.test/path']);

    expect($value)->toBe('{"url":"https://example.test/path"}');
});
