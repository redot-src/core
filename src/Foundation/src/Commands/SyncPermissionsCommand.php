<?php

namespace Redot\Commands;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
use Redot\Support\PermissionNameResolver;
use Spatie\Permission\Models\Permission;

use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto discover and sync permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissions = $this->getPermissions();

        progress(
            label: 'Syncing permissions',
            steps: $permissions,
            callback: fn ($permission) => Permission::firstOrCreate($permission),
            hint: 'This may take a while...',
        );

        info('Permissions synced successfully');
    }

    /**
     * Get the permissions.
     */
    protected function getPermissions(): Collection
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        $routes = $routes->filter(function ($route) {
            if (! $route->getName()) {
                return false;
            }

            return collect(Route::gatherRouteMiddleware($route))->contains(RoutePermission::class);
        });

        return $routes
            ->map(fn ($route) => [
                'name' => PermissionNameResolver::resolve($route),
                'guard_name' => $this->guardForRoute($route),
            ])
            ->unique()
            ->values();
    }

    /**
     * Resolve the guard a route authenticates against.
     */
    protected function guardForRoute($route): string
    {
        foreach (Route::gatherRouteMiddleware($route) as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, Authenticate::class . ':')) {
                return explode(',', substr($middleware, strlen(Authenticate::class) + 1))[0];
            }
        }

        return 'admins';
    }
}
