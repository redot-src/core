<?php

namespace Redot\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Redot\Models\Session;

trait HasSessions
{
    /**
     * The database sessions that belong to this entity.
     */
    public function sessions(): MorphToMany
    {
        return $this->morphToMany(
            Session::class,
            'user',
            'session_authentications',
            'user_id',
            'session_id',
        )->withPivot('guard');
    }

    /**
     * Log this entity out of every session except the current one.
     */
    public function logoutOtherSessions(): int
    {
        $sessions = $this->sessions()
            ->whereKeyNot(session()->getId())
            ->pluck('sessions.id');

        return Session::query()->whereKey($sessions)->delete();
    }

    /**
     * Log this entity out of every session, including the current one.
     */
    public function logoutAllSessions(): int
    {
        $sessions = $this->sessions()->pluck('sessions.id');

        return Session::query()->whereKey($sessions)->delete();
    }
}
