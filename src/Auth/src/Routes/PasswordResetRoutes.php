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
    /**
     * Register the forgot-password and reset-password routes.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();

        Route::middleware($context->guest())->group(function () use ($context, $options) {
            if (! $context->api) {
                Route::get('forgot-password', static function () use ($options): View {
                    $context = new AuthContext(...$options);

                    return view($context->views['forgot-password'], ['context' => $context]);
                })->name('password.request');
                Route::get('reset-password/{token}', static function (Request $request) use ($options): View {
                    $context = new AuthContext(...$options);

                    return view($context->views['reset-password'], ['request' => $request, 'context' => $context]);
                })->name('password.reset');
            }

            Route::post('forgot-password', static fn (Request $request): RedirectResponse|JsonResponse => app(PasswordReset::class)->send($request, new AuthContext(...$options)))->name('password.email');
            Route::post('reset-password', static fn (Request $request): RedirectResponse|JsonResponse => app(PasswordReset::class)->reset($request, new AuthContext(...$options)))->name('password.store');
        });
    }
}
