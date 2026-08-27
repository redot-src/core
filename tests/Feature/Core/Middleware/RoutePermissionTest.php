<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;

beforeEach(function () {
    $user = new User;
    $user->setAttribute('id', 1);

    $this->actingAs($user, 'admins');
});

it('denies named protected routes when no gate grants their permission', function () {
    Route::get('/permission-test/denied', fn () => 'denied')
        ->middleware(RoutePermission::class)
        ->name('permission-test.denied');

    $this->get('/permission-test/denied')->assertForbidden();
});

it('does not cache permission decisions between requests', function () {
    $allowed = true;

    Gate::define('permission-test.revoked', function (User $user) use (&$allowed) {
        return $allowed;
    });

    Route::get('/permission-test/revoked', fn () => 'revoked')
        ->middleware(RoutePermission::class)
        ->name('permission-test.revoked');

    $this->get('/permission-test/revoked')->assertOk();

    $allowed = false;

    $this->get('/permission-test/revoked')->assertForbidden();
});

it('uses one permission for create and store routes', function () {
    Gate::define('permission-test.users.create', fn (User $user) => true);

    Route::get('/permission-test/users/create', fn () => 'create')
        ->middleware(RoutePermission::class)
        ->name('permission-test.users.create');
    Route::post('/permission-test/users', fn () => 'store')
        ->middleware(RoutePermission::class)
        ->name('permission-test.users.store');

    $this->get('/permission-test/users/create')->assertOk();
    $this->post('/permission-test/users')->assertOk();
});

it('uses one permission for edit and update routes', function () {
    Gate::define('permission-test.users.edit', fn (User $user) => true);

    Route::get('/permission-test/users/1/edit', fn () => 'edit')
        ->middleware(RoutePermission::class)
        ->name('permission-test.users.edit');
    Route::patch('/permission-test/users/1', fn () => 'update')
        ->middleware(RoutePermission::class)
        ->name('permission-test.users.update');

    $this->get('/permission-test/users/1/edit')->assertOk();
    $this->patch('/permission-test/users/1')->assertOk();
});

it('honours an explicit permission alias', function () {
    Gate::define('permission-test.users.suspend', fn (User $user) => true);

    Route::post('/permission-test/users/1/suspend', fn () => 'suspended')
        ->middleware(RoutePermission::class)
        ->name('permission-test.users.suspend.store')
        ->usePermission('permission-test.users.suspend');

    $this->post('/permission-test/users/1/suspend')->assertOk();
});

it('authorizes against the guard of the route auth middleware', function () {
    config(['auth.guards.admins-api' => ['driver' => 'session', 'provider' => 'admins']]);

    Gate::define('permission-test.api.show', fn (User $user) => $user->id === 2);

    Route::get('/permission-test/api', fn () => 'api')
        ->middleware(['auth:admins-api', RoutePermission::class])
        ->name('permission-test.api.show');

    $apiUser = new User;
    $apiUser->setAttribute('id', 2);

    $this->actingAs($apiUser, 'admins-api');

    $this->get('/permission-test/api')->assertOk();

    // UI checks guess the same guard when none is given
    Route::getRoutes()->refreshNameLookups();

    expect(route_allowed('permission-test.api.show'))->toBeTrue();
});

it('denies api-guard routes when no gate grants their permission', function () {
    config(['auth.guards.admins-api' => ['driver' => 'session', 'provider' => 'admins']]);

    Route::get('/permission-test/api-denied', fn () => 'denied')
        ->middleware(['auth:admins-api', RoutePermission::class])
        ->name('permission-test.api-denied');

    $apiUser = new User;
    $apiUser->setAttribute('id', 2);

    $this->actingAs($apiUser, 'admins-api');

    $this->get('/permission-test/api-denied')->assertForbidden();
});

it('preserves the existing unnamed route bypass', function () {
    Route::get('/permission-test/unnamed', fn () => 'unnamed')
        ->middleware(RoutePermission::class);

    $this->get('/permission-test/unnamed')->assertOk();
});

it('allows permission-aware UI checks for routes that opt out of protection', function () {
    Route::get('/permission-test/unprotected', fn () => 'unprotected')
        ->name('permission-test.unprotected');

    Route::getRoutes()->refreshNameLookups();

    expect(route_allowed('permission-test.unprotected'))->toBeTrue();
});
