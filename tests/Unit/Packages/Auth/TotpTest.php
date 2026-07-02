<?php

use Redot\Auth\Support\Totp;

// RFC 6238 Appendix B test vectors (SHA1), truncated to 6 digits.
const RFC6238_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

it('generates the RFC 6238 reference codes', function (int $timestamp, string $expected) {
    expect((new Totp)->code(RFC6238_SECRET, $timestamp))->toBe($expected);
})->with([
    [59, '287082'],
    [1111111109, '081804'],
    [1111111111, '050471'],
    [1234567890, '005924'],
    [2000000000, '279037'],
    [20000000000, '353130'],
]);

it('verifies the current code and tolerates adjacent periods', function () {
    $totp = new Totp;
    $secret = $totp->generateSecret();

    expect($totp->verify($secret, $totp->code($secret)))->toBeTrue()
        ->and($totp->verify($secret, $totp->code($secret, time() - Totp::PERIOD)))->toBeTrue()
        ->and($totp->verify($secret, $totp->code($secret, time() + Totp::PERIOD)))->toBeTrue()
        ->and($totp->verify($secret, $totp->code($secret, time() - 10 * Totp::PERIOD)))->toBeFalse();
});

it('ignores whitespace in submitted codes', function () {
    $totp = new Totp;
    $secret = $totp->generateSecret();
    $code = $totp->code($secret);

    expect($totp->verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3)))->toBeTrue();
});

it('rejects codes that do not match', function () {
    $totp = new Totp;
    $secret = $totp->generateSecret();
    $wrong = $totp->code($secret) === '000000' ? '000001' : '000000';

    expect($totp->verify($secret, $wrong))->toBeFalse();
});

it('generates base32 secrets of the requested length', function () {
    $secret = (new Totp)->generateSecret(16);

    expect($secret)->toHaveLength(16)
        ->and($secret)->toMatch('/^[A-Z2-7]+$/');
});

it('throws when the secret contains invalid base32 characters', function () {
    (new Totp)->code('not-valid-base32!');
})->throws(InvalidArgumentException::class);

it('builds an otpauth url with the issuer, label, and secret', function () {
    $url = (new Totp)->qrCodeUrl('Redot Dashboard', 'admin@example.com', RFC6238_SECRET);

    expect($url)->toStartWith('otpauth://totp/Redot%20Dashboard:admin%40example.com?')
        ->and($url)->toContain('secret=' . RFC6238_SECRET)
        ->and($url)->toContain('issuer=Redot+Dashboard')
        ->and($url)->toContain('digits=6')
        ->and($url)->toContain('period=30');
});
