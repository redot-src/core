<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\TwoFactor;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

/**
 * Registers the two-factor challenge and management routes for an auth context.
 */
class TwoFactorRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        $action = app(TwoFactor::class);

        Route::middleware($context->guest())->group(function () use ($context, $action) {
            if (! $context->api) {
                Route::get('two-factor-challenge', fn (Request $request): View|RedirectResponse => $action->challenge($request, $context))->name('two-factor.challenge');
            }

            Route::post('two-factor-challenge', fn (Request $request): RedirectResponse|JsonResponse => $action->verify($request, $context))->name('two-factor.challenge.store');
            Route::post('two-factor-challenge/{method}', fn (Request $request, string $method): RedirectResponse|JsonResponse => $action->send($request, $context, $method))->middleware('throttle:6,1')->name('two-factor.challenge.send');
        });

        Route::middleware($context->auth())->group(function () use ($context, $action) {
            if ($context->api || isset($context->views['two-factor'])) {
                Route::get('two-factor', fn (Request $request): View|JsonResponse => $action->edit($request, $context))->name('two-factor.edit');
            }

            Route::post('two-factor/recovery-codes', fn (Request $request): RedirectResponse|JsonResponse => $action->recoveryCodes($request, $context))->name('two-factor.recovery-codes.store');
            Route::post('two-factor/{method}', fn (Request $request, string $method): RedirectResponse|JsonResponse => $action->store($request, $context, $method))->middleware('throttle:6,1')->name('two-factor.store');
            Route::post('two-factor/{method}/confirm', fn (Request $request, string $method): RedirectResponse|JsonResponse => $action->confirm($request, $context, $method))->name('two-factor.confirm');
            Route::delete('two-factor/{method}', fn (Request $request, string $method): RedirectResponse|JsonResponse => $action->destroy($request, $context, $method))->name('two-factor.destroy');
        });
    }
}
