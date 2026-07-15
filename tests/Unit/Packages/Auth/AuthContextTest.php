<?php

use Illuminate\Support\Facades\Route;
use Redot\Auth\AuthContext;

function make_auth_context(array $overrides = []): AuthContext
{
    return new AuthContext(
        guard: $overrides['guard'] ?? 'admins',
        provider: $overrides['provider'] ?? 'admins',
        broker: $overrides['broker'] ?? 'admins',
        model: $overrides['model'] ?? config('auth.providers.admins.model'),
        scope: $overrides['scope'] ?? null,
        api: $overrides['api'] ?? false,
        namePrefix: $overrides['namePrefix'] ?? 'dashboard.',
        views: $overrides['views'] ?? ['unlock' => 'auth.unlock'],
        home: $overrides['home'] ?? 'dashboard.index',
        identifiers: $overrides['identifiers'] ?? ['email'],
        disable: $overrides['disable'] ?? [],
    );
}

it('prefixes feature route names with the configured name prefix', function () {
    $context = make_auth_context(['namePrefix' => 'dashboard.']);

    expect($context->routeName('login'))->toBe('dashboard.login')
        ->and($context->routeName('logout'))->toBe('dashboard.logout');
});

it('uses the single identifier as the input name when only one identifier is configured', function () {
    $context = make_auth_context(['identifiers' => ['email']]);

    expect($context->identifierInputName())->toBe('email');
});

it('uses the generic "identifier" input name when multiple identifiers are configured', function () {
    $context = make_auth_context(['identifiers' => ['email', 'phone']]);

    expect($context->identifierInputName())->toBe('identifier');
});

it('builds a guest middleware list scoped to the configured guard', function () {
    $context = make_auth_context(['guard' => 'admins']);

    expect($context->guest())->toBe(['guest:admins']);
});

it('appends the locked middleware to the auth list when the lock screen is enabled', function () {
    $context = make_auth_context([
        'guard' => 'admins',
        'namePrefix' => 'dashboard.',
        'views' => ['unlock' => 'auth.unlock'],
    ]);

    expect($context->auth())->toBe([
        'auth:admins',
        $context->lockedMiddleware(),
    ]);
});

it('omits the locked middleware when the unlock view is not configured', function () {
    $context = make_auth_context([
        'guard' => 'admins',
        'views' => [],
    ]);

    expect($context->auth())->toBe(['auth:admins'])
        ->and($context->featureEnabled('lock-screen'))->toBeFalse();
});

it('omits the locked middleware when the context is an api context', function () {
    $context = make_auth_context([
        'api' => true,
        'views' => ['unlock' => 'auth.unlock'],
    ]);

    expect($context->featureEnabled('lock-screen'))->toBeFalse()
        ->and($context->auth())->toBe(['auth:' . $context->guard]);
});

it('disables magic links when the context is an api context', function () {
    $context = make_auth_context([
        'api' => true,
        'views' => ['magic-link' => 'auth.magic-link'],
    ]);

    expect($context->featureEnabled('magic-link'))->toBeFalse();
});

it('honours the explicit feature disable list', function () {
    $context = make_auth_context([
        'disable' => ['register', 'magic-link'],
    ]);

    expect($context->featureEnabled('register'))->toBeFalse()
        ->and($context->featureEnabled('magic-link'))->toBeFalse()
        ->and($context->featureEnabled('logout'))->toBeTrue();
});

it('ignores unsupported feature names passed through the disable list', function () {
    $context = make_auth_context(['disable' => ['something-not-real']]);

    expect($context->featureEnabled('something-not-real'))->toBeTrue();
});

it('resolves the home url via the route name when home is a string', function () {
    Route::name('dashboard.index')->get('/home', fn () => 'home');

    $context = make_auth_context(['home' => 'dashboard.index']);

    expect($context->homeUrl())->toBe(route('dashboard.index'));
});

it('resolves the home url via the supplied closure when home is callable', function () {
    $context = make_auth_context(['home' => fn () => 'https://example.test/welcome']);

    expect($context->homeUrl())->toBe('https://example.test/welcome');
});

it('restores callback values after serialization', function () {
    $context = make_auth_context([
        'scope' => fn (mixed $query): mixed => $query,
        'home' => fn (): string => 'https://example.test/welcome',
    ]);

    $restored = unserialize(serialize($context));

    expect($restored->scope)->toBeInstanceOf(Closure::class)
        ->and(($restored->scope)('query'))->toBe('query')
        ->and($restored->home)->toBeInstanceOf(Closure::class)
        ->and($restored->homeUrl())->toBe('https://example.test/welcome');
});
