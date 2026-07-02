<?php

use Illuminate\Support\Facades\Schema;
use Redot\Auth\Support\Totp;
use Tests\Fixtures\TwoFactorUser;

beforeEach(function () {
    Schema::create('two_factor_users', function ($table) {
        $table->id();
        $table->string('email');
        $table->text('two_factor_secret')->nullable();
        $table->text('two_factor_recovery_codes')->nullable();
        $table->timestamp('two_factor_confirmed_at')->nullable();
        $table->timestamp('two_factor_email_confirmed_at')->nullable();
    });
});

function make_two_factor_user(array $attributes = []): TwoFactorUser
{
    return TwoFactorUser::create(['email' => 'user@example.com', ...$attributes]);
}

it('reports two factor enabled when any registered method is enabled', function () {
    $none = make_two_factor_user();
    $totp = make_two_factor_user(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now()]);
    $email = make_two_factor_user(['two_factor_email_confirmed_at' => now()]);
    $pending = make_two_factor_user(['two_factor_secret' => encrypt('SECRET')]);

    expect($none->hasEnabledTwoFactorAuthentication())->toBeFalse()
        ->and($totp->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($email->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($pending->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('decrypts the stored secret', function () {
    $user = make_two_factor_user(['two_factor_secret' => encrypt('SECRET')]);

    expect($user->twoFactorSecret())->toBe('SECRET');
});

it('hides the secret and recovery codes from serialization', function () {
    $user = make_two_factor_user([
        'two_factor_secret' => encrypt('SECRET'),
        'two_factor_recovery_codes' => encrypt(json_encode(['a-b'])),
    ]);

    expect($user->toArray())->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes']);
});

it('builds an otpauth qr code url from the app name and email', function () {
    config(['app.name' => 'Redot']);

    $secret = (new Totp)->generateSecret();
    $user = make_two_factor_user(['two_factor_secret' => encrypt($secret)]);

    expect($user->twoFactorQrCodeUrl())->toStartWith('otpauth://totp/Redot:user%40example.com?')
        ->and($user->twoFactorQrCodeUrl())->toContain('secret=' . $secret);
});

it('returns null for the qr code url when no secret exists', function () {
    expect(make_two_factor_user()->twoFactorQrCodeUrl())->toBeNull();
});

it('generates, stores, and returns recovery codes', function () {
    $user = make_two_factor_user();
    $codes = $user->generateRecoveryCodes();

    expect($codes)->toHaveCount(8)
        ->and($user->recoveryCodes())->toBe($codes)
        ->and($user->fresh()->two_factor_recovery_codes)->not->toBeNull();
});

it('replaces only the used recovery code', function () {
    $user = make_two_factor_user();
    $codes = $user->generateRecoveryCodes();

    $user->replaceRecoveryCode($codes[0]);
    $fresh = $user->recoveryCodes();

    expect($fresh)->toHaveCount(8)
        ->and($fresh)->not->toContain($codes[0])
        ->and(array_slice($fresh, 1))->toBe(array_slice($codes, 1));
});

it('keeps recovery codes untouched when replacing an unknown code', function () {
    $user = make_two_factor_user();
    $codes = $user->generateRecoveryCodes();

    $user->replaceRecoveryCode('unknown-code');

    expect($user->recoveryCodes())->toBe($codes);
});

it('forgets all recovery codes', function () {
    $user = make_two_factor_user();
    $user->generateRecoveryCodes();
    $user->forgetRecoveryCodes();

    expect($user->recoveryCodes())->toBe([])
        ->and($user->fresh()->two_factor_recovery_codes)->toBeNull();
});
