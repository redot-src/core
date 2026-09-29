<?php

namespace Redot\Auth\Pipeline;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Redot\Auth\Features\Login;
use SensitiveParameter;

class AttemptToAuthenticate
{
    /**
     * Resolve the user and verify their password.
     */
    public function handle(LoginAttempt $attempt, Closure $next): mixed
    {
        $user = $attempt->panel->findUser($attempt->identifier);

        // Hash against a dummy value for unknown users so both paths cost the same.
        $hash = $user !== null ? (string) $user->getAuthPassword() : $this->dummyHash();
        $valid = Hash::check((string) $attempt->request->input('password'), $hash);

        if ($user === null || ! $valid) {
            RateLimiter::hit($attempt->key(), $attempt->panel->feature(Login::class)->getDecaySeconds());

            throw ValidationException::withMessages([
                $attempt->panel->identifierInputName() => __('auth.failed'),
            ]);
        }

        $this->rehashPasswordIfRequired($user, (string) $attempt->request->input('password'));

        $attempt->user = $user;

        return $next($attempt);
    }

    /**
     * Upgrade the stored hash when the hashing driver or cost has changed.
     */
    protected function rehashPasswordIfRequired(Model&Authenticatable $user, #[SensitiveParameter] string $password): void
    {
        if (! config('hashing.rehash_on_login', true) || ! Hash::needsRehash($user->getAuthPassword())) {
            return;
        }

        $user->forceFill([$user->getAuthPasswordName() => Hash::make($password)])->save();
    }

    /**
     * Get a hash built with the configured hasher to check unknown users against.
     */
    protected function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('redot-dummy-password');
    }
}
