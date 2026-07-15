<?php

use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
use Spatie\Permission\Models\Permission;

it('syncs every protected verb under its resolved permission', function () {
    Route::middleware(RoutePermission::class)->group(function () {
        Route::get('/permission-test/users/create', fn () => 'create')->name('permission-test.users.create');
        Route::post('/permission-test/users', fn () => 'store')->name('permission-test.users.store');
        Route::get('/permission-test/users/{user}/edit', fn () => 'edit')->name('permission-test.users.edit');
        Route::put('/permission-test/users/{user}', fn () => 'update')->name('permission-test.users.update');
        Route::delete('/permission-test/users/{user}', fn () => 'destroy')->name('permission-test.users.destroy');
        Route::post('/permission-test/users/{user}/suspend', fn () => 'suspend')
            ->name('permission-test.users.suspend.store')
            ->usePermission('permission-test.users.suspend');

        Route::resource('/permission-test/articles', stdClass::class)
            ->only(['store', 'update'])
            ->names([
                'store' => 'permission-test.articles.store',
                'update' => 'permission-test.articles.update',
            ])
            ->usePermissions([
                'store' => 'permission-test.articles.publish',
            ]);
    });

    Route::post('/permission-test/unprotected', fn () => 'unprotected')
        ->name('permission-test.unprotected');

    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::query()
        ->where('name', 'like', 'permission-test.%')
        ->orderBy('name')
        ->get(['name', 'guard_name'])
        ->map(fn (Permission $permission) => [$permission->name, $permission->guard_name])
        ->all())->toBe([
            ['permission-test.articles.edit', 'admins'],
            ['permission-test.articles.publish', 'admins'],
            ['permission-test.users.create', 'admins'],
            ['permission-test.users.destroy', 'admins'],
            ['permission-test.users.edit', 'admins'],
            ['permission-test.users.suspend', 'admins'],
        ]);
});
