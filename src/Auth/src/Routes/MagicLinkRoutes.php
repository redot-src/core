<?php

namespace Redot\Auth\Routes;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Redot\Auth\Actions\MagicLink;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RouteRegistrar;

class MagicLinkRoutes implements RouteRegistrar
{
    /**
     * Register the magic link and one-time code routes.
     */
    public function register(AuthContext $context): void
    {
        $action = app(MagicLink::class);

        Route::middleware($context->guest())->group(function () use ($context, $action) {
            Route::get('magic-link', fn (): View => view($context->views['magic-link'], ['context' => $context]))->name('magic-link.create');
            Route::post('magic-link', fn (Request $request): RedirectResponse => $action->send($request, $context))->name('magic-link.store');
            Route::get('magic-link/verify/{token}', fn (string $token): RedirectResponse => $action->verify($token, $context))->name('magic-link-code.show');
            Route::get('magic-link/code', fn (Request $request): View|RedirectResponse => $action->view($request, $context))->name('magic-link-code.create');
            Route::post('magic-link/code', fn (Request $request): RedirectResponse => $action->confirm($request, $context))->name('magic-link-code.store');
        });
    }
}
