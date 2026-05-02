<?php

namespace Tests\Fixtures\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;

class VerifiableAuthContextUser extends Model implements MustVerifyEmail
{
    public function hasVerifiedEmail(): bool
    {
        return false;
    }

    public function markEmailAsVerified(): bool
    {
        return true;
    }

    public function markEmailAsUnverified(): bool
    {
        return true;
    }

    public function sendEmailVerificationNotification(): void
    {
        //
    }

    public function getEmailForVerification(): string
    {
        return 'user@example.com';
    }
}
