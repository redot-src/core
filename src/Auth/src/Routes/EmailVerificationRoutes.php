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
    public function register(AuthContext $context): void
    {
        $action = app(EmailVerification::class);

        Route::middleware($context->auth())->group(function () use ($context, $action) {
            if (! $context->api) {
                Route::get('verify-email', fn (Request $request): RedirectResponse|View => $action->prompt($request, $context))->name('verification.notice');
            }

            Route::get('verify-email/{id}/{hash}', fn (EmailVerificationRequest $request): RedirectResponse|JsonResponse => $action->verify($request, $context))->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
            Route::post('email/verification-notification', fn (Request $request): RedirectResponse|JsonResponse => $action->send($request, $context))->middleware('throttle:6,1')->name('verification.send');
        });
    }
}
