<?php

use Illuminate\Support\Facades\Route;
use Redot\Auth\RedotAuthManager;

it('registers login, registration, logout, and email-verification routes for a configured guard by default', function () {
    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'register' => 'auth.register',
                'forgot-password' => 'auth.forgot-password',
                'reset-password' => 'auth.reset-password',
                'magic-link' => 'auth.magic-link',
            ],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('dashboard.login.store'))->toBeTrue()
        ->and(Route::has('dashboard.register.store'))->toBeTrue()
        ->and(Route::has('dashboard.logout'))->toBeTrue()
        ->and(Route::has('dashboard.verification.send'))->toBeTrue();
});

it('skips registering routes for explicitly disabled features', function () {
    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: ['login' => 'auth.login'],
            disable: ['register', 'magic-link', 'email-verification', 'lock-screen'],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('dashboard.login.store'))->toBeTrue()
        ->and(Route::has('dashboard.logout'))->toBeTrue()
        ->and(Route::has('dashboard.register.store'))->toBeFalse()
        ->and(Route::has('dashboard.verification.send'))->toBeFalse()
        ->and(Route::has('dashboard.unlock'))->toBeFalse();
});

it('throws when the requested guard is not configured', function () {
    app(RedotAuthManager::class)->routes('missing');
})->throws(InvalidArgumentException::class, 'Guard [missing] is not configured.');

it('throws when the guard provider does not point at a real model class', function () {
    config()->set('auth.guards.broken', ['driver' => 'session', 'provider' => 'broken']);
    config()->set('auth.providers.broken', ['driver' => 'eloquent', 'model' => 'App\\Does\\Not\\Exist']);

    app(RedotAuthManager::class)->routes('broken');
})->throws(InvalidArgumentException::class, 'Provider [broken] model is invalid.');
