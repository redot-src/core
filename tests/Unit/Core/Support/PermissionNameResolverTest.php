<?php

use Illuminate\Support\Facades\Route;
use Redot\Support\PermissionNameResolver;

it('groups conventional write routes with their form permissions', function (string $route, string $permission) {
    expect(PermissionNameResolver::resolve($route))->toBe($permission);
})->with([
    ['users.create', 'users.create'],
    ['users.store', 'users.create'],
    ['users.edit', 'users.edit'],
    ['users.update', 'users.edit'],
    ['users.destroy', 'users.destroy'],
    ['store', 'create'],
    ['update', 'edit'],
]);

it('uses an explicit permission for routes outside the conventional groups', function () {
    $route = Route::post('/users/{user}/suspend', fn () => 'suspended')
        ->name('users.suspend.store')
        ->usePermission('users.suspend');

    Route::getRoutes()->refreshNameLookups();

    expect(PermissionNameResolver::resolve($route))->toBe('users.suspend')
        ->and(PermissionNameResolver::resolve('users.suspend.store'))->toBe('users.suspend');
});

it('uses action-specific permissions on resource routes', function () {
    Route::resource('articles', stdClass::class)->usePermissions([
        'store' => 'articles.publish',
        'destroy' => 'articles.archive',
    ]);

    expect(PermissionNameResolver::resolve('articles.store'))->toBe('articles.publish')
        ->and(PermissionNameResolver::resolve('articles.destroy'))->toBe('articles.archive')
        ->and(PermissionNameResolver::resolve('articles.update'))->toBe('articles.edit')
        ->and(PermissionNameResolver::resolve('articles.index'))->toBe('articles.index');
});

it('mirrors api routes onto their dashboard counterparts', function () {
    Route::get('/dashboard/users', fn () => 'index')->name('dashboard.users.index');
    Route::post('/dashboard/users/{user}/suspend', fn () => 'suspend')
        ->name('dashboard.users.suspend.store')
        ->usePermission('dashboard.users.suspend');
    Route::resource('/dashboard/articles', stdClass::class)
        ->names('dashboard.articles')
        ->usePermissions(['store' => 'dashboard.articles.publish']);

    $index = Route::get('/api/dashboard/users', fn () => 'index')->name('api.dashboard.users.index');
    $suspend = Route::post('/api/dashboard/users/{user}/suspend', fn () => 'suspend')->name('api.dashboard.users.suspend.store');
    $publish = Route::post('/api/dashboard/articles', fn () => 'store')->name('api.dashboard.articles.store');

    Route::getRoutes()->refreshNameLookups();

    expect(PermissionNameResolver::resolve($index))->toBe('dashboard.users.index')
        ->and(PermissionNameResolver::resolve('api.dashboard.users.index'))->toBe('dashboard.users.index')
        ->and(PermissionNameResolver::resolve($suspend))->toBe('dashboard.users.suspend')
        ->and(PermissionNameResolver::resolve($publish))->toBe('dashboard.articles.publish');
});

it('prefers an explicit permission on the api route over the dashboard counterpart', function () {
    Route::get('/dashboard/reports', fn () => 'index')->name('dashboard.reports.index');
    $route = Route::get('/api/dashboard/reports', fn () => 'index')
        ->name('api.dashboard.reports.index')
        ->usePermission('dashboard.reports.export');

    Route::getRoutes()->refreshNameLookups();

    expect(PermissionNameResolver::resolve($route))->toBe('dashboard.reports.export');
});

it('keeps the api name when no dashboard counterpart is registered', function () {
    $route = Route::post('/api/dashboard/webhooks', fn () => 'store')->name('api.dashboard.webhooks.store');

    Route::getRoutes()->refreshNameLookups();

    expect(PermissionNameResolver::resolve($route))->toBe('api.dashboard.webhooks.create')
        ->and(PermissionNameResolver::resolve('api.dashboard.webhooks.store'))->toBe('api.dashboard.webhooks.create')
        ->and(PermissionNameResolver::dashboardRoute('api.dashboard.webhooks.store'))->toBeNull()
        ->and(PermissionNameResolver::dashboardRoute('dashboard.webhooks.store'))->toBeNull();
});
