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
        $options = $context->toArray();

        Route::middleware($context->guest())->group(function () use ($options) {
            Route::get('magic-link', static function () use ($options): View {
                $context = new AuthContext(...$options);

                return view($context->views['magic-link'], ['context' => $context]);
            })->name('magic-link.create');
            Route::post('magic-link', static fn (Request $request): RedirectResponse => app(MagicLink::class)->send($request, new AuthContext(...$options)))->name('magic-link.store');
            Route::get('magic-link/verify/{token}', static fn (string $token): RedirectResponse => app(MagicLink::class)->verify($token, new AuthContext(...$options)))->name('magic-link-code.show');
            Route::get('magic-link/code', static fn (Request $request): View|RedirectResponse => app(MagicLink::class)->view($request, new AuthContext(...$options)))->name('magic-link-code.create');
            Route::post('magic-link/code', static fn (Request $request): RedirectResponse => app(MagicLink::class)->confirm($request, new AuthContext(...$options)))->middleware('throttle:6,1')->name('magic-link-code.store');
        });
    }
}
