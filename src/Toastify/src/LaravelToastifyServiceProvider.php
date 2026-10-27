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
        $this->app->singleton(Toastify::class, fn () => new Toastify);
        $this->app->alias(Toastify::class, 'toastify');
    }
}
