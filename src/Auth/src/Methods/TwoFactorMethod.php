<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;

abstract class TwoFactorMethod
{
    abstract public function key(): string;

    abstract public function enabled(Authenticatable $user): bool;

    abstract public function enable(Authenticatable $user): array;

    abstract public function confirm(Authenticatable $user, string $code): bool;

    abstract public function disable(Authenticatable $user): void;

    abstract public function verify(Authenticatable $user, string $code): bool;

    public function pending(Authenticatable $user): bool
    {
        return false;
    }

    public function deliverable(): bool
    {
        return false;
    }

    public function send(Authenticatable $user): void
    {
        //
    }
}
