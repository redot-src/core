<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\RoutePermission;
use Redot\Support\PermissionNameResolver;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
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
        {--grant= : Assign synced permissions to an admin by id or email}
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
    public function handle(): int
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

        if ($this->option('grant') !== null) {
            if (! $this->grantPermissions($permissions, (string) $this->option('grant'))) {
                return SymfonyCommand::FAILURE;
            }
        }

        info('Permissions synced successfully');

        return SymfonyCommand::SUCCESS;
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
     * Assign the synced permissions to an admin identified by id or email.
     */
    protected function grantPermissions(Collection $permissions, string $grant): bool
    {
        $admin = $this->resolveGrantee($grant);

        if (! $admin) {
            error(sprintf('No admin found for grant target [%s]', $grant));

            return false;
        }

        if (! method_exists($admin, 'givePermissionTo')) {
            error(sprintf(
                'Admin model [%s] must use Spatie\'s HasRoles (or HasPermissions) trait to receive grants',
                $admin::class,
            ));

            return false;
        }

        if ($permissions->isEmpty()) {
            info(sprintf('No synced permissions to grant to admin [%s]', $admin->getKey()));

            return true;
        }

        $admin->givePermissionTo(
            $permissions
                ->map(fn (array $permission) => Permission::findByName($permission['name'], $permission['guard_name']))
                ->all(),
        );

        info(sprintf(
            'Granted %d permission(s) to admin [%s]',
            $permissions->count(),
            $admin->getKey(),
        ));

        return true;
    }

    /**
     * Resolve an admin model by primary key or email.
     */
    protected function resolveGrantee(string $grant): ?Model
    {
        $model = $this->adminModel();

        if (filter_var($grant, FILTER_VALIDATE_INT) !== false) {
            return $model::query()->find((int) $grant);
        }

        return $model::query()->where('email', $grant)->first();
    }

    /**
     * Resolve the eloquent model behind the admins guard.
     *
     * @return class-string<Model>
     */
    protected function adminModel(): string
    {
        $provider = config('auth.guards.admins.provider', 'admins');

        return config("auth.providers.{$provider}.model");
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
     * Resolve the guard a route's permission is stored under.
     *
     * Guards sharing a provider (e.g. session + API guards over the same
     * model) are stamped with the provider's first configured guard, so a
     * permission shared across guards is stored once.
     */
    protected function guardForRoute($route): string
    {
        $guard = route_guard($route);
        $provider = config("auth.guards.{$guard}.provider");

        foreach (config('auth.guards', []) as $name => $config) {
            if ($provider !== null && ($config['provider'] ?? null) === $provider) {
                return $name;
            }
        }

        return $guard;
    }
}
