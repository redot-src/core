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
    public function register(AuthContext $context): void
    {
        if ($context->api) {
            return;
        }

        $action = app(Lock::class);
        $locked = $context->lockedMiddleware();

        Route::middleware($context->auth())->group(function () use ($context, $action, $locked) {
            Route::post('lock', fn (Request $request): RedirectResponse|JsonResponse => $action->lock($request, $context))->name('lock');

            Route::withoutMiddleware($locked)->group(function () use ($context, $action) {
                Route::get('unlock', fn (Request $request): View|RedirectResponse => $action->view($request, $context))->name('unlock');
                Route::post('unlock', fn (Request $request): RedirectResponse|JsonResponse => $action->unlock($request, $context))->name('unlock.store');
            });
        });
    }
}
