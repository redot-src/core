<?php

namespace Tests\Fixtures\Auth;

use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ApiUser extends User
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $casts = ['password' => 'hashed'];
}
