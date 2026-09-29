<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\PasswordResetController;
use Redot\Auth\Panel;

class PasswordReset extends Feature
{
    /**
     * Register the forgot-password and reset-password routes.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->guestMiddleware())->controller(PasswordResetController::class)->group(function () use ($panel) {
            if (! $panel->isApi()) {
                Route::get('forgot-password', 'request')->name('password.request');
                Route::get('reset-password/{token}', 'edit')->name('password.reset');
            }

            Route::post('forgot-password', 'email')->name('password.email');
            Route::post('reset-password', 'update')->name('password.store');
        });
    }
}
