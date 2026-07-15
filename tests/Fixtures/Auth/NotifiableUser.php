<?php

namespace Tests\Fixtures\Auth;

use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;

class NotifiableUser extends User
{
    use Notifiable;

    protected $table = 'users';

    // Allow all attributes to be mass assigned.
    protected $guarded = [];
}
