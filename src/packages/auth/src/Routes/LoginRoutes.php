<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\Login;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class LoginRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        $action = app(Login::class);

        Route::middleware($context->guest())->group(function () use ($context, $action) {
            if (! $context->api) {
                Route::get('login', fn (): View => view($context->views['login'], ['context' => $context]))->name('login');
            }

            Route::post('login', fn (Request $request): RedirectResponse|JsonResponse => $action->authenticate($request, $context))->name('login.store');
        });
    }
}
