<?php

use Redot\Http\Controllers\FallbackController;
use Redot\Http\Middleware\Localization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Redot\Auth\Middleware\Locked;
use Redot\Http\Middleware\EnsureDependenciesBuilt;
use Redot\Http\Middleware\RoutePermission;

return Application::configure(isset($basePath) ? $basePath : null)
    ->withRouting(function () {
        if (app()->runningUnitTests()) {
            config(['redot.features.website-api.enabled' => true]);
            config(['redot.features.dashboard-api.enabled' => true]);
            config(['redot.features.website.enabled' => true]);
            config(['redot.features.dashboard.enabled' => true]);
        }

        // Load the API routes for the website and dashboard
        Route::middleware('api')->as('api.')->prefix('api')->group(function () {
            if (config('redot.features.website-api.enabled')) {
                Route::as('website.')->group(base_path('routes/api/website.php'));
            }

            if (config('redot.features.dashboard-api.enabled')) {
                Route::as('dashboard.')->prefix(config('redot.features.dashboard-api.prefix'))->group(base_path('routes/api/dashboard.php'));
            }
        });

        // Load the global routes
        Route::as('global.')->middleware('web')->group(base_path('routes/global.php'));

        // Load the website and dashboard routes
        $group = Route::middleware('web');

        if (config('redot.routing.append_locale_to_url')) {
            $group->prefix('{locale}')->where(['locale' => '([a-zA-Z]{2})']);
        }

        $group->group(function () {
            if (config('redot.features.website.enabled')) {
                Route::as('website.')->group(base_path('routes/website.php'));
            }

            if (config('redot.features.dashboard.enabled')) {
                Route::as('dashboard.')->prefix(config('redot.features.dashboard.prefix'))->middleware('dashboard')->group(base_path('routes/dashboard.php'));
            }
        });

        // Load the fallback route
        Route::fallback(FallbackController::class)->middleware('web');
    })

    ->withCommands([__DIR__ . '/../routes/console.php'])

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(remove: [
            SubstituteBindings::class,
        ]);

        $middleware->web(append: [
            Localization::class,
            SubstituteBindings::class,
            EnsureDependenciesBuilt::class,
        ]);

        $middleware->group('dashboard', [
            RoutePermission::class,
            Locked::class . ':admins,dashboard.unlock',
        ]);

        $middleware->api(append: [
            'throttle:api',
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson() || $request->is('api/*');
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return throw_api_exception($e);
        });
    })->create();
