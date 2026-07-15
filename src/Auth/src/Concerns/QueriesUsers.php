<?php

namespace Redot\Auth\Concerns;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Redot\Auth\AuthContext;

trait QueriesUsers
{
    /**
     * Find the user matching the given identifier value, honouring the context scope.
     */
    protected function findUserByIdentifier(string $value, AuthContext $context): (Model&Authenticatable)|null
    {
        $query = $this->applyScope($context->model::query(), $context->scope);
        $identifiers = $context->identifiers;

        $query = $query->where(function (Builder $q) use ($identifiers, $value) {
            foreach ($identifiers as $column) {
                $q->orWhere($column, $value);
            }
        });

        return $query->first();
    }

    /**
     * Apply the context's custom scope to the user query.
     */
    protected function applyScope(Builder $query, ?Closure $scope): Builder
    {
        if ($scope === null) {
            return $query;
        }

        $result = $scope($query);

        return $result instanceof Builder ? $result : $query;
    }

    /**
     * Record the user's last login time, if the model supports it.
     */
    protected function touchLastLoginAt(Authenticatable|Model $user): void
    {
        if (! $user->isFillable('last_login_at')) {
            return;
        }

        // Memoized per connection and table: the schema cannot change mid-process,
        // so pay the introspection query at most once instead of on every login.
        static $hasColumn = [];

        $key = $user->getConnectionName() . ':' . $user->getTable();
        $hasColumn[$key] ??= $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'last_login_at');

        if (! $hasColumn[$key]) {
            return;
        }

        $user->update(['last_login_at' => now()]);
    }
}
