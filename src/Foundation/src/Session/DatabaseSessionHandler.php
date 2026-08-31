<?php

namespace Redot\Session;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Session\DatabaseSessionHandler as BaseHandler;
use Illuminate\Support\Collection;

class DatabaseSessionHandler extends BaseHandler
{
    /**
     * Persist the session and synchronize every authenticated session guard.
     */
    public function write($sessionId, $data): bool
    {
        $written = parent::write($sessionId, $data);

        $this->syncAuthentications($sessionId);

        return $written;
    }

    /**
     * Session ownership is stored in the per-guard association table.
     */
    protected function addUserInformation(&$payload)
    {
        return $this;
    }

    /**
     * Resolve authenticated users keyed by session guard.
     */
    protected function authenticatedUsers(): Collection
    {
        if (! $this->container) {
            return collect();
        }

        $auth = $this->container->make('auth');
        $guards = $this->container->make('config')->get('auth.guards', []);

        return collect($guards)
            ->filter(fn (array $guard) => ($guard['driver'] ?? null) === 'session')
            ->map(fn (array $guard, string $name): ?Authenticatable => $auth->guard($name)->user())
            ->filter();
    }

    /**
     * Synchronize the polymorphic owners of a browser session.
     */
    protected function syncAuthentications(string $sessionId): void
    {
        if (! $this->container) {
            return;
        }

        $users = $this->authenticatedUsers();
        $query = $this->connection->table('session_authentications')
            ->where('session_id', $sessionId);

        $query->clone()
            ->whereNotIn('guard', $users->keys())
            ->delete();

        $users->each(function (Authenticatable $user, string $guard) use ($sessionId) {
            $this->connection->table('session_authentications')->updateOrInsert(
                ['session_id' => $sessionId, 'guard' => $guard],
                [
                    'user_type' => $user->getMorphClass(),
                    'user_id' => $user->getAuthIdentifier(),
                ],
            );
        });
    }
}
