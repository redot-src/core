<?php

use Illuminate\Support\Facades\Route;
use Redot\Auth\AuthContext;
use Redot\Auth\RedotAuthManager;
use Tests\Fixtures\Auth\CapturingLoginRoutes;
use Tests\Fixtures\Auth\VerifiableAuthContextUser;

it('resolves route context for configured guards and custom registrars', function () {
    CapturingLoginRoutes::$context = null;

    config()->set('auth.guards.admins', ['driver' => 'session', 'provider' => 'admins']);
    config()->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => VerifiableAuthContextUser::class]);
    config()->set('auth.passwords.admins', ['provider' => 'admins']);

    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: ['login' => 'auth.login'],
            disable: ['register', 'password-reset', 'magic-link', 'email-verification', 'logout', 'lock-screen'],
            registrars: ['login' => CapturingLoginRoutes::class],
            home: 'dashboard.index',
        );
    });

    expect(CapturingLoginRoutes::$context)->toBeInstanceOf(AuthContext::class)
        ->guard->toBe('admins')
        ->provider->toBe('admins')
        ->broker->toBe('admins')
        ->api->toBeFalse()
        ->namePrefix->toBe('dashboard.')
        ->home->toBe('dashboard.index');
});

it('rejects missing auth guard configuration', function () {
    app(RedotAuthManager::class)->routes('missing');
})->throws(InvalidArgumentException::class, 'Guard [missing] is not configured.');
