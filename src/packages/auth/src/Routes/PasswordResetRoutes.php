<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\PasswordReset;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class PasswordResetRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        $action = app(PasswordReset::class);

        Route::middleware($context->guest())->group(function () use ($context, $action) {
            if (! $context->api) {
                Route::get('forgot-password', fn (): View => view($context->views['forgot-password'], ['context' => $context]))->name('password.request');
                Route::get('reset-password/{token}', fn (Request $request): View => view($context->views['reset-password'], ['request' => $request, 'context' => $context]))->name('password.reset');
            }

            Route::post('forgot-password', fn (Request $request): RedirectResponse|JsonResponse => $action->sendResetLink($request, $context))->name('password.email');
            Route::post('reset-password', fn (Request $request): RedirectResponse|JsonResponse => $action->reset($request, $context))->name('password.store');
        });
    }
}
