<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Redot\Sidebar\Sidebar;
use Redot\Toastify\Toastify;

it('boots the package testbench application', function () {
    expect(app()->bound(Sidebar::class))->toBeTrue()
        ->and(app()->make(Sidebar::class))->toBeInstanceOf(Sidebar::class)
        ->and(app('sidebar'))->toBe(app()->make(Sidebar::class))
        ->and(app()->bound(Toastify::class))->toBeTrue()
        ->and(app('toastify'))->toBeInstanceOf(Toastify::class);
});

it('merges package configuration', function () {
    expect(config('redot.features.dashboard.enabled'))->toBeTrue()
        ->and(config('redot.features.dashboard.prefix'))->toBe('dashboard')
        ->and(config('redot.locales'))->toHaveCount(2)
        ->and(config('datatables.assets'))->toBeArray()
        ->and(config('toastify.defaults'))->toBeArray()
        ->and(config('toastify.toastifiers.success'))->toBeArray();
});

it('runs package migrations in the testbench database', function () {
    foreach ([
        'settings',
        'languages',
        'language_tokens',
        'login_tokens',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Expected table [$table] to exist.");
    }
});

it('registers package artisan commands', function () {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain(
        'uploads:clear',
        'permissions:sync',
        'lang:extract',
        'lang:sync',
        'lang:publish',
        'lang:revert',
        'make:datatable',
    );
});
