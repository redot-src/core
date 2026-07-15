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

it('stamps discovered permissions without touching manually created ones', function () {
    Route::middleware(RoutePermission::class)->group(function () {
        Route::get('/permission-test/users', fn () => 'index')->name('permission-test.users.index');
    });

    $custom = Permission::create(['name' => 'permission-test.custom', 'guard_name' => 'admins']);

    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::findByName('permission-test.users.index', 'admins')->discovered_at)->not->toBeNull()
        ->and($custom->fresh()->discovered_at)->toBeNull();
});

it('prunes stale discovered permissions but keeps custom and current ones', function () {
    Route::middleware(RoutePermission::class)->group(function () {
        Route::get('/permission-test/users', fn () => 'index')->name('permission-test.users.index');
    });

    Permission::create(['name' => 'permission-test.stale', 'guard_name' => 'admins', 'discovered_at' => now()]);
    Permission::create(['name' => 'permission-test.custom', 'guard_name' => 'admins']);

    $this->artisan('permissions:sync --prune')
        ->expectsConfirmation('Delete 1 stale permission(s)?', 'yes')
        ->assertSuccessful();

    expect(Permission::query()
        ->where('name', 'like', 'permission-test.%')
        ->orderBy('name')
        ->pluck('name')
        ->all())->toBe([
            'permission-test.custom',
            'permission-test.users.index',
        ]);
});

it('keeps stale permissions when pruning is not confirmed', function () {
    Route::middleware(RoutePermission::class)->group(function () {
        Route::get('/permission-test/users', fn () => 'index')->name('permission-test.users.index');
    });

    Permission::create(['name' => 'permission-test.stale', 'guard_name' => 'admins', 'discovered_at' => now()]);

    $this->artisan('permissions:sync --prune')
        ->expectsConfirmation('Delete 1 stale permission(s)?')
        ->assertSuccessful();

    expect(Permission::where('name', 'permission-test.stale')->exists())->toBeTrue();
});
