<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\EmailVerificationController;
use Redot\Auth\Panel;

class EmailVerification extends Feature
{
    /**
     * Register the email verification prompt, confirmation, and resend routes.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->authMiddleware())->controller(EmailVerificationController::class)->group(function () use ($panel) {
            if (! $panel->isApi()) {
                Route::get('verify-email', 'notice')->name('verification.notice');
            }

            Route::get('verify-email/{id}/{hash}', 'verify')->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
            Route::post('email/verification-notification', 'send')->middleware('throttle:6,1')->name('verification.send');
        });
    }
}
