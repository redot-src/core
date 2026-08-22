<?php

namespace Tests\Fixtures\Auth;

use Illuminate\Foundation\Auth\User;
use Redot\Traits\HasSessions;

class SessionMember extends User
{
    use HasSessions;

    protected $table = 'session_members';

    protected $guarded = [];
}
