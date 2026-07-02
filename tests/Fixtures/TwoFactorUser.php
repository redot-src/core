<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Redot\Auth\Concerns\TwoFactorAuthenticatable;

class TwoFactorUser extends User
{
    use HasApiTokens, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'two_factor_users';

    // Allow all attributes to be mass assigned.
    protected $guarded = [];

    public $timestamps = false;
}
