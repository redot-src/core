<?php

use Illuminate\Container\Container;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Redot\Application;

it('enables features for shared tests unless opted out and respects them outside testing', function (string $feature, string $route, bool $enabled, ?bool $enableAllFeatures, string $environment) {
    $originalEnvironment = $this->app['env'];
    $basePath = $this->app->basePath();
    $routes = Route::getRoutes();
    $property = new ReflectionProperty(RouteServiceProvider::class, 'alwaysLoadRoutesUsing');
    $original = $property->getValue();

    try {
        Application::configure(dirname(__DIR__, 2) . '/Fixtures/Application');
        $registerRoutes = $property->getValue();
        Container::setInstance($this->app);
        $this->app->setBasePath(dirname(__DIR__, 2) . '/Fixtures/Application');
        Route::setRoutes(new RouteCollection);
        config(["redot.features.{$feature}.enabled" => $enabled]);

        $this->app['env'] = $environment;
        if ($enableAllFeatures !== null) {
            config(['redot.testing.enable_all_features' => $enableAllFeatures]);
        }
        $expected = $enabled || ($environment === 'testing' && ($enableAllFeatures ?? true));

        $registerRoutes();
        Route::getRoutes()->refreshNameLookups();

        expect(app()->runningUnitTests())->toBe($environment === 'testing')
            ->and(config("redot.features.{$feature}.enabled"))->toBe($expected)
            ->and(Route::has($route))->toBe($expected)
            ->and(Route::has('global.index'))->toBeTrue();
    } finally {
        $this->app['env'] = $originalEnvironment;
        Container::setInstance($this->app);
        $this->app->setBasePath($basePath);
        Route::setRoutes($routes);
        RouteServiceProvider::loadRoutesUsing($original);
    }
})->with([
    ['website', 'website.index'],
    ['dashboard', 'dashboard.index'],
    ['website-api', 'api.website.index'],
    ['dashboard-api', 'api.dashboard.index'],
])->with([true, false])->with([
    'shared suite default' => [null, 'testing'],
    'feature configuration tests' => [false, 'testing'],
    'production' => [true, 'production'],
]);
