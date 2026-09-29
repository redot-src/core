<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\RegistrationController;
use Redot\Auth\Panel;

class Registration extends Feature
{
    /**
     * Register the registration screen and sign-up endpoint.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->guestMiddleware())->group(function () use ($panel) {
            if (! $panel->isApi()) {
                Route::get('register', [RegistrationController::class, 'create'])->name('register');
            }

            Route::post('register', [RegistrationController::class, 'store'])->name('register.store');
        });
    }
}
