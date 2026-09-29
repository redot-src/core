<?php

namespace Redot\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;

trait RecordsLastLogin
{
    /**
     * Record the user's last login time, if the model supports it.
     */
    protected function recordLastLogin(Model $user): void
    {
        if (! $user->isFillable('last_login_at')) {
            return;
        }

        // The schema cannot change mid-process, so check each table only once.
        static $hasColumn = [];

        $key = $user->getConnectionName() . ':' . $user->getTable();
        $hasColumn[$key] ??= $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'last_login_at');

        if ($hasColumn[$key]) {
            $user->update(['last_login_at' => now()]);
        }
    }
}
