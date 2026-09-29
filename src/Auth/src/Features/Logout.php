<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\LogoutController;
use Redot\Auth\Panel;

class Logout extends Feature
{
    /**
     * Register the logout endpoint, reachable even while the session is locked.
     */
    public function routes(Panel $panel): void
    {
        $route = Route::match(['delete', 'post'], 'logout', LogoutController::class)
            ->middleware($panel->authMiddleware())
            ->name('logout');

        if ($panel->hasFeature(LockScreen::class)) {
            $route->withoutMiddleware($panel->lockedMiddleware());
        }
    }
}
