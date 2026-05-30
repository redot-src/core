<?php

namespace Redot\Auth;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Facades\RedotAuth as RedotAuthFacade;

class RedotAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedotAuthManager::class);
    }

    public function boot(): void
    {
        AliasLoader::getInstance()->alias('RedotAuth', RedotAuthFacade::class);
    }
}
