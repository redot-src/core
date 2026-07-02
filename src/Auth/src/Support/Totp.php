<?php

namespace Redot\Auth\Support;

use InvalidArgumentException;

class Totp
{
    /**
     * The number of seconds a generated code remains valid.
     */
    public const PERIOD = 30;

    /**
     * The number of digits in a generated code.
     */
    public const DIGITS = 6;

    /**
     * The base32 alphabet used for secrets (RFC 4648).
     */
    protected const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new base32-encoded shared secret.
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= static::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Verify a code against the secret, allowing adjacent periods for clock drift.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        $timestamp = time();

        foreach (range(-$window, $window) as $offset) {
            if (hash_equals($this->code($secret, $timestamp + $offset * static::PERIOD), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the code for the given secret and timestamp (RFC 6238).
     */
    public function code(string $secret, ?int $timestamp = null): string
    {
        $counter = intdiv($timestamp ?? time(), static::PERIOD);
        $hash = hash_hmac('sha1', pack('J', $counter), $this->base32Decode($secret), true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** static::DIGITS)), static::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Build the otpauth:// URL that authenticator apps read from a QR code.
     */
    public function qrCodeUrl(string $issuer, string $label, string $secret): string
    {
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => static::DIGITS,
            'period' => static::PERIOD,
        ]);

        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($label) . '?' . $query;
    }

    /**
     * Decode a base32-encoded secret into its binary representation.
     */
    protected function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        $bits = '';
        $binary = '';

        foreach (str_split($secret) as $char) {
            $index = strpos(static::ALPHABET, $char);

            if ($index === false) {
                throw new InvalidArgumentException('The secret contains invalid base32 characters.');
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }

        return $binary;
    }
}
