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
    /**
     * Register the logout endpoint.
     */
    public function register(AuthContext $context): void
    {
        $options = $context->toArray();

        Route::middleware($context->auth())->group(function () use ($context, $options) {
            $route = Route::match(['delete', 'post'], 'logout', static fn (Request $request): RedirectResponse|JsonResponse => app(Logout::class)->logout($request, new AuthContext(...$options)));

            if ($context->featureEnabled('lock-screen')) {
                $route->withoutMiddleware($context->lockedMiddleware());
            }

            $route->name('logout');
        });
    }
}
