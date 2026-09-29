<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\LockScreenController;
use Redot\Auth\Panel;

class LockScreen extends Feature
{
    /**
     * The middleware groups that redirect to the unlock screen while locked.
     */
    protected array $groups = [];

    /**
     * Also send the routes of the given middleware groups to the unlock screen while locked.
     */
    public function protect(string ...$groups): static
    {
        $this->groups = array_merge($this->groups, $groups);

        return $this;
    }

    /**
     * Get the middleware groups protected by the lock screen.
     */
    public function getProtectedGroups(): array
    {
        return $this->groups;
    }

    /**
     * Locking relies on the session, so API panels cannot use it.
     */
    public function supportsApi(): bool
    {
        return false;
    }

    /**
     * Register the lock and unlock routes.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->authMiddleware())->controller(LockScreenController::class)->group(function () use ($panel) {
            Route::post('lock', 'lock')->name('lock');

            Route::withoutMiddleware($panel->lockedMiddleware())->group(function () {
                Route::get('unlock', 'show')->name('unlock');
                Route::post('unlock', 'unlock')->middleware('throttle:6,1')->name('unlock.store');
            });
        });
    }
}
