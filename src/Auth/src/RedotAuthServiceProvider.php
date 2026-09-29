<?php

namespace Redot\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\Actions\CreateUser;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Facades\RedotAuth as RedotAuthFacade;

class RedotAuthServiceProvider extends ServiceProvider
{
    /**
     * Register the auth manager and default actions.
     */
    public function register(): void
    {
        $this->app->singleton(RedotAuthManager::class);
        $this->app->bind(CreatesUsers::class, CreateUser::class);
        $this->app->bind(Panel::class, fn ($app) => $app->make(RedotAuthManager::class)->current($app['request']));
    }

    /**
     * Register the RedotAuth facade alias, lock screen middleware, and email verification listener.
     */
    public function boot(): void
    {
        AliasLoader::getInstance()->alias('RedotAuth', RedotAuthFacade::class);

        // Panels are defined in other providers' boot(), so wait until they all ran.
        $this->app->booted(function () {
            $this->app->make(RedotAuthManager::class)->protectLockedGroups();

            $listeners = Event::getRawListeners();
            $listeners = $listeners[Registered::class] ?? [];

            if (! in_array(SendEmailVerificationNotification::class, $listeners, true)) {
                Event::listen(Registered::class, SendEmailVerificationNotification::class);
            }
        });
    }
}
