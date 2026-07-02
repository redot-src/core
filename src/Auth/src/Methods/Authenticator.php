<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Redot\Auth\Support\Totp;

class Authenticator extends TwoFactorMethod
{
    public function key(): string
    {
        return 'totp';
    }

    public function enabled(Authenticatable $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    public function pending(Authenticatable $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    }

    public function enable(Authenticatable $user): array
    {
        $user->forceFill([
            'two_factor_secret' => encrypt(app(Totp::class)->generateSecret()),
            'two_factor_confirmed_at' => null,
        ])->save();

        return [
            'secret' => $user->twoFactorSecret(),
            'qr_code_url' => $user->twoFactorQrCodeUrl(),
        ];
    }

    public function confirm(Authenticatable $user, string $code): bool
    {
        if (! $this->pending($user) || ! app(Totp::class)->verify((string) $user->twoFactorSecret(), $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    public function disable(Authenticatable $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function verify(Authenticatable $user, string $code): bool
    {
        return $this->enabled($user) && app(Totp::class)->verify((string) $user->twoFactorSecret(), $code);
    }
}
