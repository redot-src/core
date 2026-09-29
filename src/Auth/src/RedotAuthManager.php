<?php

namespace Redot\Auth;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Http\Middleware\ResolvePanel;

class RedotAuthManager
{
    /**
     * The defined panels, keyed by name.
     *
     * @var array<string, Panel>
     */
    protected array $panels = [];

    /**
     * Get the panel with the given name, defining it on first use.
     */
    public function panel(string $name): Panel
    {
        return $this->panels[$name] ??= new Panel($name);
    }

    /**
     * Get the defined panel with the given name.
     */
    public function get(string $name): Panel
    {
        return $this->panels[$name] ?? throw new InvalidArgumentException("Auth panel [$name] is not defined.");
    }

    /**
     * Determine whether a panel with the given name is defined.
     */
    public function hasPanel(string $name): bool
    {
        return isset($this->panels[$name]);
    }

    /**
     * Register the routes of the given panel's features.
     */
    public function routes(string $name): void
    {
        $prefix = $this->currentNamePrefix();
        $panel = $this->get($name)->withRoutePrefix($prefix);

        $this->validate($panel);

        Route::middleware(ResolvePanel::using($name, $prefix))->group(function () use ($panel) {
            foreach ($panel->getFeatures() as $feature) {
                $feature->routes($panel);
            }
        });
    }

    /**
     * Add the lock middleware to the middleware groups each panel's lock screen protects.
     */
    public function protectLockedGroups(): void
    {
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = app(Kernel::class);

        foreach ($this->panels as $panel) {
            if (! $panel->hasFeature(LockScreen::class)) {
                continue;
            }

            foreach ($panel->feature(LockScreen::class)->getProtectedGroups() as $group) {
                $kernel->appendMiddlewareToGroup($group, $panel->lockedMiddleware());
            }
        }
    }

    /**
     * Get the panel resolved for the given request.
     */
    public function current(Request $request): Panel
    {
        return $request->attributes->get(Panel::class)
            ?? throw new InvalidArgumentException('The current route does not belong to an auth panel.');
    }

    /**
     * Fail early on misconfigured panels instead of at request time.
     */
    protected function validate(Panel $panel): void
    {
        $panel->model();

        if (! $panel->isApi() && ! Auth::guard($panel->getGuard()) instanceof StatefulGuard) {
            throw new InvalidArgumentException("Guard [{$panel->getGuard()}] cannot start sessions, mark auth panel [$panel->name] as api().");
        }

        foreach ($panel->getFeatures() as $feature) {
            if ($panel->isApi() && ! $feature->supportsApi()) {
                throw new InvalidArgumentException('Feature [' . $feature::class . "] is not supported by API panel [$panel->name].");
            }
        }
    }

    /**
     * Get the route name prefix of the innermost active route group.
     */
    protected function currentNamePrefix(): string
    {
        $stack = Route::getGroupStack();

        if ($stack === []) return '';

        return (string) ($stack[array_key_last($stack)]['as'] ?? '');
    }
}
