<?php

namespace Redot\Auth\Routes;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\EmailVerification;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class EmailVerificationRoutes implements RouteRegistrar
{
    /**
     * Register the email verification prompt, confirmation, and resend routes.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();

        Route::middleware($context->auth())->group(function () use ($context, $options) {
            if (! $context->api) {
                Route::get('verify-email', static fn (Request $request): RedirectResponse|View => app(EmailVerification::class)->prompt($request, new AuthContext(...$options)))->name('verification.notice');
            }

            Route::get('verify-email/{id}/{hash}', static fn (EmailVerificationRequest $request): RedirectResponse|JsonResponse => app(EmailVerification::class)->verify($request, new AuthContext(...$options)))->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
            Route::post('email/verification-notification', static fn (Request $request): RedirectResponse|JsonResponse => app(EmailVerification::class)->send($request, new AuthContext(...$options)))->middleware('throttle:6,1')->name('verification.send');
        });
    }
}
