<?php

namespace Redot\Commands;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
use Redot\Support\PermissionNameResolver;
use Spatie\Permission\Models\Permission;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\table;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        permissions:sync
        {--prune : Delete previously discovered permissions that no longer match a route}
        {--force : Force prune permissions without asking for confirmation}
    ';

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
            callback: fn ($permission) => $this->syncPermission($permission),
            hint: 'This may take a while...',
        );

        if ($this->option('prune')) {
            $this->prunePermissions($permissions);
        }

        info('Permissions synced successfully');
    }

    /**
     * Persist the given permission and stamp it as auto-discovered.
     */
    protected function syncPermission(array $permission): void
    {
        $model = Permission::firstOrCreate($permission, ['discovered_at' => now()]);

        if (! $model->discovered_at) {
            $model->forceFill(['discovered_at' => now()])->save();
        }
    }

    /**
     * Delete previously discovered permissions that no longer match a route.
     *
     * Manually created permissions are never stamped with `discovered_at`,
     * so they always survive pruning.
     */
    protected function prunePermissions(Collection $permissions): void
    {
        $discovered = $permissions->map(fn ($permission) => $permission['name'] . '|' . $permission['guard_name']);

        $stale = Permission::query()
            ->whereNotNull('discovered_at')
            ->get()
            ->reject(fn (Permission $permission) => $discovered->contains($permission->name . '|' . $permission->guard_name));

        if ($stale->isEmpty()) {
            info('No stale permissions to prune');

            return;
        }

        table(['Permission', 'Guard'], $stale->map(fn (Permission $permission) => [$permission->name, $permission->guard_name])->all());

        // Only ask for confirmation if the --force option is not provided.
        if (! $this->option('force') && ! confirm(sprintf('Delete %d stale permission(s)?', $stale->count()))) {
            return;
        }

        // Delete through the models so Spatie's permission cache is flushed.
        $stale->each->delete();

        info(sprintf('Pruned %d stale permission(s)', $stale->count()));
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
