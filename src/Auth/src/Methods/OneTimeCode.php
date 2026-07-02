<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Base class for methods that deliver a hashed, single-use code to the user.
 *
 * Codes expire after auth.two_factor.expire minutes. Subclasses supply the
 * timestamp column that marks the method as confirmed and the delivery channel.
 */
abstract class OneTimeCode extends TwoFactorMethod
{
    /**
     * The timestamp column that marks the method as confirmed for a user.
     */
    abstract protected function column(): string;

    /**
     * Deliver the plain code to the user.
     */
    abstract protected function deliver(Authenticatable $user, string $code): void;

    /**
     * Determine whether the user's confirmation column is set.
     */
    public function enabled(Authenticatable $user): bool
    {
        return $user->{$this->column()} !== null;
    }

    /**
     * Determine whether a setup code is outstanding for an unconfirmed user.
     */
    public function pending(Authenticatable $user): bool
    {
        return ! $this->enabled($user) && Cache::has($this->cacheKey($user, 'setup'));
    }

    /**
     * Begin setup by sending the user a confirmation code.
     */
    public function enable(Authenticatable $user): array
    {
        $this->issue($user, 'setup');

        return [];
    }

    /**
     * Confirm a pending setup with the delivered code.
     */
    public function confirm(Authenticatable $user, string $code): bool
    {
        if (! $this->check($user, 'setup', $code)) {
            return false;
        }

        $user->forceFill([$this->column() => now()])->save();

        return true;
    }

    /**
     * Disable the method and discard any outstanding codes.
     */
    public function disable(Authenticatable $user): void
    {
        $user->forceFill([$this->column() => null])->save();

        Cache::forget($this->cacheKey($user, 'setup'));
        Cache::forget($this->cacheKey($user, 'challenge'));
    }

    /**
     * Verify a challenge code against the cached single-use code.
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        return $this->enabled($user) && $this->check($user, 'challenge', $code);
    }

    /**
     * One-time code methods always deliver codes to the user.
     */
    public function deliverable(): bool
    {
        return true;
    }

    /**
     * Issue and deliver a fresh challenge code.
     */
    public function send(Authenticatable $user): void
    {
        $this->issue($user, 'challenge');
    }

    /**
     * Issue a fresh code for the given purpose and deliver it to the user.
     */
    protected function issue(Authenticatable $user, string $purpose): void
    {
        $code = (string) random_int(100000, 999999);
        $expire = (int) config('auth.two_factor.expire', 10);

        Cache::put($this->cacheKey($user, $purpose), Hash::make($code), now()->addMinutes($expire));

        $this->deliver($user, $code);
    }

    /**
     * Check a submitted code for the given purpose. A successful check
     * consumes the code; failed checks leave it in place.
     */
    protected function check(Authenticatable $user, string $purpose, string $code): bool
    {
        $hash = Cache::get($this->cacheKey($user, $purpose));

        if (! is_string($hash) || $code === '' || ! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($this->cacheKey($user, $purpose));

        return true;
    }

    /**
     * Get the cache key that holds the user's code for the given purpose.
     */
    protected function cacheKey(Authenticatable $user, string $purpose): string
    {
        return sprintf('two-factor:%s:%s:%s:%s', $this->key(), $purpose, strtolower(class_basename($user)), $user->getKey());
    }
}
