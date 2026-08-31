<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('session.table', 'sessions');
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('session.connection') ?? parent::getConnectionName();
    }
}
