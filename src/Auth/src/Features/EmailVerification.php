<?php

namespace Redot\Auth\Features;

use Closure;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\EmailVerificationController;
use Redot\Auth\Panel;

class EmailVerification extends Feature
{
    /**
     * The route name or callback that resolves where verified users are sent.
     */
    protected string|Closure|null $redirectTo = null;

    /**
     * Send verified users to the given route name or callback's URL instead of the panel's home.
     */
    public function redirectTo(string|Closure $redirectTo): static
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    /**
     * Get the URL verified users are sent to when they weren't headed anywhere.
     */
    public function getRedirectUrl(Panel $panel): string
    {
        if ($this->redirectTo instanceof Closure) {
            return ($this->redirectTo)($panel);
        }

        return $this->redirectTo === null ? $panel->homeUrl() : route($this->redirectTo);
    }

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
