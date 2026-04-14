<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Actions\Logout;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class LogoutRoutes implements RouteRegistrar
{
    public function register(AuthContext $context): void
    {
        $action = app(Logout::class);

        Route::middleware($context->auth())->group(function () use ($context, $action) {
            $route = match ($context->api) {
                true => Route::delete('logout', fn (Request $request): RedirectResponse|JsonResponse => $action->logout($request, $context)),
                false => Route::post('logout', fn (Request $request): RedirectResponse|JsonResponse => $action->logout($request, $context)),
            };

            if ($context->featureEnabled('lock-screen')) {
                $route->withoutMiddleware($context->lockedMiddleware());
            }

            $route->name('logout');
        });
    }
}
