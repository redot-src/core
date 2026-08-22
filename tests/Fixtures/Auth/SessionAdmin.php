<?php

namespace Tests\Fixtures\Auth;

use Illuminate\Foundation\Auth\User;
use Redot\Traits\HasSessions;

class SessionAdmin extends User
{
    use HasSessions;

    protected $table = 'session_admins';

    protected $guarded = [];
}
