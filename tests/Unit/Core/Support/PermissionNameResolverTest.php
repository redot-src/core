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
