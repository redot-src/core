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
            ->and(Route::has('global.index'))->toBeTrue()
            ->and(Route::has('global.select.search'))->toBeTrue()
            ->and(Route::has('global.uploader.upload'))->toBeTrue()
            ->and(Route::has('global.hugerte.upload'))->toBeTrue();
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

it('loads the application route files from the configured paths', function () {
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
        config(['redot.paths.routes.website' => 'routes/custom/website.php']);

        $registerRoutes();
        Route::getRoutes()->refreshNameLookups();

        expect(Route::has('website.custom'))->toBeTrue()
            ->and(Route::has('website.index'))->toBeFalse();
    } finally {
        Container::setInstance($this->app);
        $this->app->setBasePath($basePath);
        Route::setRoutes($routes);
        RouteServiceProvider::loadRoutesUsing($original);
    }
});

it('registers each package endpoint with its own middleware unless disabled', function (string $endpoint, array $routeNames) {
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
        config(["redot.components.{$endpoint}.middleware" => ['throttle:5,1']]);

        $registerRoutes();
        Route::getRoutes()->refreshNameLookups();

        foreach ($routeNames as $name) {
            expect(Route::getRoutes()->getByName($name)->middleware())->toContain('throttle:5,1');
        }

        expect(Route::getRoutes()->getByName('global.index')->middleware())->not->toContain('throttle:5,1');

        Route::setRoutes(new RouteCollection);
        config(["redot.components.{$endpoint}.enabled" => false]);

        $registerRoutes();
        Route::getRoutes()->refreshNameLookups();

        foreach ($routeNames as $name) {
            expect(Route::has($name))->toBeFalse();
        }

        expect(Route::has('global.index'))->toBeTrue();
    } finally {
        Container::setInstance($this->app);
        $this->app->setBasePath($basePath);
        Route::setRoutes($routes);
        RouteServiceProvider::loadRoutesUsing($original);
    }
})->with([
    'select' => ['select', ['global.select.search', 'global.select.fetch']],
    'uploader' => ['uploader', ['global.uploader.upload']],
    'hugerte' => ['hugerte', ['global.hugerte.upload']],
]);
