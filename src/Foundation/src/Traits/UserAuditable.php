<?php

namespace Redot\Traits;

trait UserAuditable
{
    /**
     * Boot the trait to hook into model events.
     */
    public static function bootUserAuditable()
    {
        $guard = static::getUserAuditableGuard();

        static::creating(function ($model) use ($guard) {
            if (! $model->isDirty('created_by') && auth($guard)->check()) {
                $model->created_by = auth($guard)->id();
            }
        });

        static::updating(function ($model) use ($guard) {
            if (! array_key_exists('updated_by', $model->attributesToArray())) {
                return;
            }

            if (! $model->isDirty('updated_by') && auth($guard)->check()) {
                $model->updated_by = auth($guard)->id();
            }
        });

        static::deleting(function ($model) use ($guard) {
            if (! array_key_exists('deleted_by', $model->attributesToArray())) {
                return;
            }

            if (! $model->isDirty('deleted_by') && auth($guard)->check()) {
                $model->deleted_by = auth($guard)->id();
                $model->save();
            }
        });
    }

    /**
     * The authentication guard for the model.
     */
    public static function getUserAuditableGuard()
    {
        return config('auth.defaults.guard');
    }

    /**
     * The provider for the authentication guard.
     */
    public static function getUserAuditableProvider()
    {
        return config('auth.providers.' . static::getUserAuditableGuard() . '.model');
    }

    /**
     * Get the user that created the model.
     */
    public function createdBy()
    {
        return $this->userAuditableRelation('created_by');
    }

    /**
     * Get the user that updated the model.
     */
    public function updatedBy()
    {
        return $this->userAuditableRelation('updated_by');
    }

    /**
     * Get the user that deleted the model.
     */
    public function deletedBy()
    {
        return $this->userAuditableRelation('deleted_by');
    }

    /**
     * Build the auditable user relation for the given foreign key.
     */
    protected function userAuditableRelation(string $foreignKey)
    {
        $provider = $this->getUserAuditableProvider();

        return $provider ? $this->belongsTo($provider, $foreignKey) : null;
    }
}
