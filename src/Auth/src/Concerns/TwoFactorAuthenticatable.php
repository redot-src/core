<?php

namespace Redot\Auth\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Redot\Auth\Actions\TwoFactor;
use Redot\Auth\Support\Totp;

/**
 * Equips a user model for two-factor authentication.
 */
trait TwoFactorAuthenticatable
{
    /**
     * Merge the two-factor casts and hide the secret columns.
     */
    public function initializeTwoFactorAuthenticatable(): void
    {
        $this->mergeCasts([
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_email_confirmed_at' => 'datetime',
        ]);

        $this->makeHidden(['two_factor_secret', 'two_factor_recovery_codes']);
    }

    /**
     * Determine whether the user has any two-factor method enabled.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return TwoFactor::enabledMethods($this)->isNotEmpty();
    }

    /**
     * Get the decrypted authenticator secret.
     */
    public function twoFactorSecret(): ?string
    {
        return $this->two_factor_secret === null ? null : decrypt($this->two_factor_secret);
    }

    /**
     * Get the otpauth URL authenticator apps read from a QR code.
     */
    public function twoFactorQrCodeUrl(): ?string
    {
        if ($this->two_factor_secret === null) {
            return null;
        }

        return app(Totp::class)->qrCodeUrl(
            (string) config('app.name'),
            (string) $this->email,
            (string) $this->twoFactorSecret(),
        );
    }

    /**
     * Get the user's recovery codes.
     *
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        if ($this->two_factor_recovery_codes === null) {
            return [];
        }

        return json_decode(decrypt($this->two_factor_recovery_codes), true);
    }

    /**
     * Generate and store a fresh set of recovery codes.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = Collection::times($count, fn (): string => Str::random(10) . '-' . Str::random(10))->all();

        $this->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($codes))])->save();

        return $codes;
    }

    /**
     * Swap a used recovery code for a fresh one.
     */
    public function replaceRecoveryCode(string $code): void
    {
        $codes = $this->recoveryCodes();
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return;
        }

        $codes[$index] = Str::random(10) . '-' . Str::random(10);

        $this->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($codes))])->save();
    }

    /**
     * Remove all recovery codes.
     */
    public function forgetRecoveryCodes(): void
    {
        $this->forceFill(['two_factor_recovery_codes' => null])->save();
    }
}
