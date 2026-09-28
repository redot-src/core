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
    /**
     * Register the registration screen and sign-up endpoint.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();

        Route::middleware($context->guest())->group(function () use ($context, $options) {
            if (! $context->api) {
                Route::get('register', static function () use ($options): View {
                    $context = new AuthContext(...$options);

                    return view($context->views['register'], ['context' => $context]);
                })->name('register');
            }

            Route::post('register', static fn (Request $request): RedirectResponse|JsonResponse => app(Registration::class)->register($request, new AuthContext(...$options)))->name('register.store');
        });
    }
}
