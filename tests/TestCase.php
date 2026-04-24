<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\SanctumServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Redot\RedotServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists($this->app['config']->get('view.compiled'));
    }

    /**
     * Get the package service providers for the testbench application.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SanctumServiceProvider::class,
            PermissionServiceProvider::class,
            RedotServiceProvider::class,
        ];
    }

    /**
     * Define the testbench environment.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('permission.testing', true);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('view.compiled', __DIR__ . '/../storage/framework/views');
    }

    /**
     * Load the database migrations for the testbench application.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
