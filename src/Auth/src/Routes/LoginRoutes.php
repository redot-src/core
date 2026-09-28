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
    /**
     * Register the login screen and sign-in endpoint.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();

        Route::middleware($context->guest())->group(function () use ($context, $options) {
            if (! $context->api) {
                Route::get('login', static function () use ($options): View {
                    $context = new AuthContext(...$options);

                    return view($context->views['login'], ['context' => $context]);
                })->name('login');
            }

            Route::post('login', static fn (Request $request): RedirectResponse|JsonResponse => app(Login::class)->authenticate($request, new AuthContext(...$options)))->name('login.store');
        });
    }
}
