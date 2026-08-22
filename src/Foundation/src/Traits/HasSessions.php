<?php

namespace Redot\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Redot\Models\Session;

trait HasSessions
{
    /**
     * The database sessions that belong to this entity.
     */
    public function sessions(): MorphMany
    {
        return $this->morphMany(Session::class, 'user');
    }

    /**
     * Log this entity out of every session except the current one.
     */
    public function logoutOtherSessions(): int
    {
        return $this->sessions()
            ->whereKeyNot(session()->getId())
            ->delete();
    }

    /**
     * Log this entity out of every session, including the current one.
     */
    public function logoutAllSessions(): int
    {
        return $this->sessions()->delete();
    }
}
