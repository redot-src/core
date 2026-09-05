<?php

namespace Redot\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Facades\RedotAuth as RedotAuthFacade;

class RedotAuthServiceProvider extends ServiceProvider
{
    /**
     * Register the auth manager singleton.
     */
    public function register(): void
    {
        $this->app->singleton(RedotAuthManager::class);
    }

    /**
     * Register the RedotAuth facade alias and email verification listener.
     */
    public function boot(): void
    {
        AliasLoader::getInstance()->alias('RedotAuth', RedotAuthFacade::class);

        $this->app->booted(function () {
            $listeners = Event::getRawListeners();
            $listeners = $listeners[Registered::class] ?? [];

            if (! in_array(SendEmailVerificationNotification::class, $listeners, true)) {
                Event::listen(Registered::class, SendEmailVerificationNotification::class);
            }
        });
    }
}
