<?php

namespace Tests;

use Illuminate\Foundation\Auth\User;
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

        // Parallel workers create the same directories at once, so tolerate one that already exists.
        File::makeDirectory($this->app['config']->get('view.compiled'), 0755, true, true);
        File::makeDirectory(dist_path(), 0755, true, true);
    }

    /**
     * Resolve the application with a public directory private to the current test process.
     *
     * Tests build, publish, and upload into the public directory, and the package
     * registers its publish paths before the environment is defined.
     */
    protected function resolveApplication()
    {
        return tap(parent::resolveApplication(), fn ($app) => $app->usePublicPath(static::processPath('public')));
    }

    /**
     * Get a scratch directory private to the current test process.
     */
    protected static function processPath(string $path): string
    {
        $token = $_SERVER['TEST_TOKEN'] ?? getenv('TEST_TOKEN') ?: 'serial';

        return sys_get_temp_dir() . "/redot-core-tests/{$token}/{$path}";
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
        $app['config']->set('view.compiled', static::processPath('views'));

        $app['config']->set('auth.guards.admins', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => User::class]);
        $app['config']->set('auth.passwords.admins', ['provider' => 'admins']);
    }

    /**
     * Load the database migrations for the testbench application.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/src/Foundation/database/migrations');
    }
}
