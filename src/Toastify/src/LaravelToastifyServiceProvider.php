<?php

namespace Redot\Toastify;

use Illuminate\Support\ServiceProvider;

class LaravelToastifyServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/toastify.php', 'toastify');

        $this->app->singleton(Toastify::class, fn () => new Toastify);
        $this->app->alias(Toastify::class, 'toastify');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/toastify.php' => config_path('toastify.php'),
        ], 'toastify::config');
    }
}
