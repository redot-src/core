<?php

namespace Redot\Session;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Session\DatabaseSessionHandler as BaseHandler;

class DatabaseSessionHandler extends BaseHandler
{
    /**
     * Add the authenticated user's polymorphic identity to the session payload.
     *
     * Unlike the base handler, this inspects every session-based guard rather
     * than only the default one, so each guard's users (admins, users, ...) are
     * recorded as separate owners and can be queried independently.
     */
    protected function addUserInformation(&$payload)
    {
        $user = $this->authenticatedUser();

        $payload['user_type'] = $user?->getMorphClass();
        $payload['user_id'] = $user?->getAuthIdentifier();

        return $this;
    }

    /**
     * Resolve the first authenticated user across all session guards.
     */
    protected function authenticatedUser(): ?Authenticatable
    {
        if (! $this->container) {
            return null;
        }

        $auth = $this->container->make('auth');
        $guards = $this->container->make('config')->get('auth.guards', []);

        foreach ($guards as $name => $guard) {
            if (($guard['driver'] ?? null) === 'session' && $auth->guard($name)->check()) {
                return $auth->guard($name)->user();
            }
        }

        return null;
    }
}
