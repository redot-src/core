<?php

namespace Redot\Auth\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Redot\Auth\AuthContext;
use Throwable;

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
    protected function applyScope(Builder $query, ?\Closure $scope): Builder
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
        try {
            $user->update(['last_login_at' => now()]);
        } catch (Throwable) {
            // Ignore errors if the model lacks last_login_at.
        }
    }
}
