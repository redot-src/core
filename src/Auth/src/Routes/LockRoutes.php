<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\Lock;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class LockRoutes implements RouteRegistrar
{
    /**
     * Register the lock and unlock routes.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();
        $locked = $context->lockedMiddleware();

        Route::middleware($context->auth())->group(function () use ($options, $locked) {
            Route::post('lock', static fn (Request $request): RedirectResponse|JsonResponse => app(Lock::class)->lock($request, new AuthContext(...$options)))->name('lock');

            Route::withoutMiddleware($locked)->group(function () use ($options) {
                Route::get('unlock', static fn (Request $request): View|RedirectResponse => app(Lock::class)->view($request, new AuthContext(...$options)))->name('unlock');
                Route::post('unlock', static fn (Request $request): RedirectResponse|JsonResponse => app(Lock::class)->unlock($request, new AuthContext(...$options)))->name('unlock.store');
            });
        });
    }
}
