<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\Registration;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class RegistrationRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        $action = app(Registration::class);

        Route::middleware($context->guest())->group(function () use ($context, $action) {
            if (! $context->api) {
                Route::get('register', fn (): View => view($context->views['register'], ['context' => $context]))->name('register');
            }

            Route::post('register', fn (Request $request): RedirectResponse|JsonResponse => $action->register($request, $context))->name('register.store');
        });
    }
}
