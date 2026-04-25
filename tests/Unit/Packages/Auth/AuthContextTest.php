<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Redot\Auth\AuthContext;
use Redot\Auth\Middleware\Locked;
use Tests\Fixtures\Auth\VerifiableAuthContextUser;

it('builds route names middleware and identifier input names from context', function () {
    Route::get('/home', fn () => 'home')->name('dashboard.index');
    Route::getRoutes()->refreshNameLookups();
    URL::setRoutes(Route::getRoutes());

    $context = new AuthContext(
        guard: 'admins',
        provider: 'admins',
        broker: 'admins',
        model: VerifiableAuthContextUser::class,
        scope: null,
        api: false,
        namePrefix: 'dashboard.',
        views: ['unlock' => 'auth.unlock'],
        home: 'dashboard.index',
        identifiers: ['email', 'phone'],
    );

    expect($context->routeName('login'))->toBe('dashboard.login')
        ->and($context->identifierInputName())->toBe('identifier')
        ->and($context->guest())->toBe(['guest:admins'])
        ->and($context->auth())->toBe([
            'auth:admins',
            Locked::class . ':admins,dashboard.unlock',
        ])
        ->and($context->homeUrl())->toBe(route('dashboard.index'));
});

it('disables unsupported auth features from context configuration', function () {
    $context = new AuthContext(
        guard: 'web',
        provider: 'users',
        broker: 'users',
        model: stdClass::class,
        scope: null,
        api: true,
        namePrefix: '',
        views: [],
        home: 'home',
        disable: ['register', 'magic-link'],
    );

    expect($context->featureEnabled('register'))->toBeFalse()
        ->and($context->featureEnabled('magic-link'))->toBeFalse()
        ->and($context->featureEnabled('email-verification'))->toBeFalse()
        ->and($context->featureEnabled('lock-screen'))->toBeFalse()
        ->and($context->auth())->toBe(['auth:web']);
});
