<?php

namespace Redot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'token',
        'code',
        'guard',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new login token for the given email and guard.
     */
    public static function generate(string $email, string $guard): self
    {
        // Delete any existing tokens for this email and guard
        static::where('email', $email)->where('guard', $guard)->delete();

        return static::create([
            'email' => $email,
            'token' => Str::random(64),
            'code' => Str::random(6),
            'guard' => $guard,
            'expires_at' => now()->addMinutes((int) config('auth.magic_link.expire', 15)),
        ]);
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include valid (non-expired) tokens.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to filter by guard.
     */
    public function scopeForGuard($query, string $guard)
    {
        return $query->where('guard', $guard);
    }

    /**
     * Find a valid token by token string.
     */
    public static function findByToken(string $token, string $guard): ?self
    {
        return static::where('token', $token)
            ->forGuard($guard)
            ->valid()
            ->first();
    }

    /**
     * Find a valid token by code and email.
     */
    public static function findByCode(string $code, string $email, string $guard): ?self
    {
        return static::where('code', $code)
            ->where('email', $email)
            ->forGuard($guard)
            ->valid()
            ->first();
    }
}
