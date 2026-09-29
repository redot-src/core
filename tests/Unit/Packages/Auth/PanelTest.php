<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Features\Login;
use Redot\Auth\Features\Logout;
use Redot\Auth\Http\Middleware\Locked;
use Redot\Auth\Panel;
use Redot\Auth\Responses\LoginResponse;
use Redot\Auth\Responses\LogoutResponse;
use Redot\Auth\Responses\ResetLinkSentResponse;
use Redot\Auth\Responses\UnlockedResponse;
use Symfony\Component\HttpFoundation\Response;

it('names the identifier input after the column when there is only one', function () {
    expect((new Panel('dashboard'))->identifierInputName())->toBe('email')
        ->and((new Panel('dashboard'))->identifiers(['email', 'phone'])->identifierInputName())->toBe('identifier');
});

it('prefixes route names with the prefix it was registered under', function () {
    $panel = (new Panel('dashboard'))->withRoutePrefix('dashboard.');

    expect($panel->routeName('login'))->toBe('dashboard.login');
});

it('does not leak the route prefix into the panel definition', function () {
    $panel = new Panel('dashboard');
    $panel->withRoutePrefix('dashboard.');

    expect($panel->routeName('login'))->toBe('login');
});

it('resolves view names from the prefix and per-screen overrides', function () {
    $panel = (new Panel('dashboard'))
        ->views('dashboard.auth')
        ->views(['unlock' => 'custom.unlock']);

    expect($panel->viewName('login'))->toBe('dashboard.auth.login')
        ->and($panel->viewName('unlock'))->toBe('custom.unlock');
});

it('resolves the home url from a route name, a callback, or the index route', function () {
    Route::get('/home', fn () => 'home')->name('dashboard.index');
    Route::get('/welcome', fn () => 'welcome')->name('welcome');
    Route::getRoutes()->refreshNameLookups();

    $panel = (new Panel('dashboard'))->withRoutePrefix('dashboard.');

    expect($panel->homeUrl())->toBe(route('dashboard.index'))
        ->and($panel->home('welcome')->homeUrl())->toBe(route('welcome'))
        ->and($panel->home(fn (Panel $panel) => 'https://example.test/' . $panel->name)->homeUrl())->toBe('https://example.test/dashboard');
});

it('adds the locked middleware to authenticated routes only when the lock screen is enabled', function () {
    $panel = (new Panel('dashboard'))->guard('admins')->withRoutePrefix('dashboard.');

    expect($panel->authMiddleware())->toBe(['auth:admins']);

    $panel->features(LockScreen::make());

    expect($panel->authMiddleware())->toBe(['auth:admins', Locked::using('admins')]);
});

it('prefers the panel binding, then the container binding, then the class itself', function () {
    $global = new class extends LogoutResponse {};
    $panel = new class extends LogoutResponse {};

    expect((new Panel('web'))->make(LogoutResponse::class))->toBeInstanceOf(LogoutResponse::class);

    app()->bind(LogoutResponse::class, $global::class);

    expect((new Panel('web'))->make(LogoutResponse::class))->toBeInstanceOf($global::class)
        ->and((new Panel('web'))->using(LogoutResponse::class, $panel::class)->make(LogoutResponse::class))->toBeInstanceOf($panel::class)
        ->and((new Panel('web'))->using(LogoutResponse::class, fn (Panel $p) => new $panel)->make(LogoutResponse::class))->toBeInstanceOf($panel::class);
});

it('passes constructor parameters to the resolved class', function () {
    $response = (new Panel('web'))->make(ResetLinkSentResponse::class, ['status' => 'passwords.sent']);

    expect($response->status)->toBe('passwords.sent');
});

it('keeps the api response when only the web response is overridden', function () {
    $custom = new class(new User, 'plain-token') extends LoginResponse
    {
        protected function web(Panel $panel): Response
        {
            return redirect('/welcome');
        }
    };

    expect($custom->toResponse((new Panel('web'))))->toBeInstanceOf(RedirectResponse::class)
        ->and($custom->toResponse((new Panel('api'))->api())->getData(true)['payload'])->toBe(['token' => 'plain-token', 'token_type' => 'Bearer']);
});

it('fails loudly when a response has no version for the panel type', function () {
    (new UnlockedResponse)->toResponse((new Panel('api'))->api());
})->throws(LogicException::class, 'has no API response.');

it('returns the configured feature instance', function () {
    $login = Login::make()->throttle(3);
    $panel = (new Panel('dashboard'))->features($login);

    expect($panel->feature(Login::class))->toBe($login)
        ->and($panel->hasFeature(Logout::class))->toBeFalse();
});

it('resolves the provider, model, and password broker from the guard', function () {
    $panel = (new Panel('dashboard'))->guard('admins');

    expect($panel->provider())->toBe('admins')
        ->and($panel->model())->toBe(User::class)
        ->and($panel->broker())->toBe('admins');
});

it('throws when the guard is missing or not configured', function (Panel $panel, string $message) {
    expect(fn () => $panel->getGuard())->toThrow(InvalidArgumentException::class, $message);
})->with([
    'no guard' => [fn () => new Panel('dashboard'), 'Auth panel [dashboard] has no guard.'],
    'unknown guard' => [fn () => (new Panel('dashboard'))->guard('missing'), 'Guard [missing] is not configured.'],
]);

it('throws when the guard provider does not point at a real model class', function () {
    config()->set('auth.guards.broken', ['driver' => 'session', 'provider' => 'broken']);
    config()->set('auth.providers.broken', ['driver' => 'eloquent', 'model' => 'App\\Does\\Not\\Exist']);

    (new Panel('broken'))->guard('broken')->model();
})->throws(InvalidArgumentException::class, 'Provider [broken] model is invalid.');
