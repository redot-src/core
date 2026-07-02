<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

abstract class OneTimeCode extends TwoFactorMethod
{
    abstract protected function column(): string;

    abstract protected function deliver(Authenticatable $user, string $code): void;

    public function enabled(Authenticatable $user): bool
    {
        return $user->{$this->column()} !== null;
    }

    public function pending(Authenticatable $user): bool
    {
        return ! $this->enabled($user) && Cache::has($this->cacheKey($user, 'setup'));
    }

    public function enable(Authenticatable $user): array
    {
        $this->issue($user, 'setup');

        return [];
    }

    public function confirm(Authenticatable $user, string $code): bool
    {
        if (! $this->check($user, 'setup', $code)) {
            return false;
        }

        $user->forceFill([$this->column() => now()])->save();

        return true;
    }

    public function disable(Authenticatable $user): void
    {
        $user->forceFill([$this->column() => null])->save();

        Cache::forget($this->cacheKey($user, 'setup'));
        Cache::forget($this->cacheKey($user, 'challenge'));
    }

    public function verify(Authenticatable $user, string $code): bool
    {
        return $this->enabled($user) && $this->check($user, 'challenge', $code);
    }

    public function deliverable(): bool
    {
        return true;
    }

    public function send(Authenticatable $user): void
    {
        $this->issue($user, 'challenge');
    }

    protected function issue(Authenticatable $user, string $purpose): void
    {
        $code = (string) random_int(100000, 999999);
        $expire = (int) config('auth.two_factor.expire', 10);

        Cache::put($this->cacheKey($user, $purpose), Hash::make($code), now()->addMinutes($expire));

        $this->deliver($user, $code);
    }

    protected function check(Authenticatable $user, string $purpose, string $code): bool
    {
        $hash = Cache::get($this->cacheKey($user, $purpose));

        if (! is_string($hash) || $code === '' || ! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($this->cacheKey($user, $purpose));

        return true;
    }

    protected function cacheKey(Authenticatable $user, string $purpose): string
    {
        return sprintf('two-factor:%s:%s:%s:%s', $this->key(), $purpose, strtolower(class_basename($user)), $user->getKey());
    }
}
