<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Redot\Auth\Support\Totp;

/**
 * Authenticator-app method backed by time-based one-time passwords (RFC 6238).
 */
class Authenticator extends TwoFactorMethod
{
    /**
     * The method is keyed as "totp".
     */
    public function key(): string
    {
        return 'totp';
    }

    /**
     * Determine whether the user has a confirmed secret.
     */
    public function enabled(Authenticatable $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Determine whether the user has an unconfirmed secret awaiting setup.
     */
    public function pending(Authenticatable $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    }

    /**
     * Generate a fresh secret and return it with its otpauth QR code URL.
     */
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

    /**
     * Confirm the pending secret with a code from the authenticator app.
     */
    public function confirm(Authenticatable $user, string $code): bool
    {
        if (! $this->pending($user) || ! app(Totp::class)->verify((string) $user->twoFactorSecret(), $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Disable the method and discard the secret.
     */
    public function disable(Authenticatable $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Verify a challenge code against the confirmed secret.
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        return $this->enabled($user) && app(Totp::class)->verify((string) $user->twoFactorSecret(), $code);
    }
}
