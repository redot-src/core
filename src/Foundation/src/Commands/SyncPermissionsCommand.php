<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
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
            callback: fn ($permission) => Permission::firstOrCreate(['name' => $permission]),
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

            if (! in_array('GET', $route->methods()) && ! in_array('DELETE', $route->methods())) {
                return false;
            }

            return collect(Route::gatherRouteMiddleware($route))->contains(RoutePermission::class);
        });

        return $routes->map(fn ($route) => $route->getName());
    }
}
