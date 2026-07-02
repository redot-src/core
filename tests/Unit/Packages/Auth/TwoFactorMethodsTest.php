<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Redot\Auth\Actions\TwoFactor;
use Redot\Auth\Methods\Authenticator;
use Redot\Auth\Methods\Email;
use Redot\Auth\Support\Totp;
use Redot\Notifications\TwoFactorCodeNotification;
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

    $this->user = TwoFactorUser::create(['email' => 'user@example.com']);
});

it('registers the authenticator and email methods by default', function () {
    expect(TwoFactor::methods()->keys()->all())->toBe(['totp', 'email']);
});

it('walks the authenticator method through its full lifecycle', function () {
    $method = new Authenticator;
    $user = $this->user;

    expect($method->enabled($user))->toBeFalse();

    $payload = $method->enable($user);

    expect($method->pending($user))->toBeTrue()
        ->and($payload['secret'])->toBe($user->twoFactorSecret())
        ->and($payload['qr_code_url'])->toContain('otpauth://totp/');

    expect($method->confirm($user, '000000'))->toBeFalse()
        ->and($method->confirm($user, (new Totp)->code($payload['secret'])))->toBeTrue()
        ->and($method->enabled($user))->toBeTrue()
        ->and($method->pending($user))->toBeFalse();

    expect($method->verify($user, (new Totp)->code($payload['secret'])))->toBeTrue()
        ->and($method->verify($user, '000000'))->toBeFalse();

    $method->disable($user);

    expect($method->enabled($user))->toBeFalse()
        ->and($user->two_factor_secret)->toBeNull();
});

it('walks the email method through its full lifecycle', function () {
    Notification::fake();

    $method = new Email;
    $user = $this->user;

    $method->enable($user);

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    expect($method->pending($user))->toBeTrue()
        ->and($method->confirm($user, '000000'))->toBeFalse()
        ->and($method->confirm($user, $code))->toBeTrue()
        ->and($method->enabled($user))->toBeTrue();

    $method->send($user);

    $challenge = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$challenge) {
        $challenge = $notification->code;

        return true;
    });

    expect($method->verify($user, $challenge))->toBeTrue()
        ->and($method->verify($user, $challenge))->toBeFalse();

    $method->disable($user);

    expect($method->enabled($user))->toBeFalse();
});

it('does not verify a totp code while the secret is unconfirmed', function () {
    $method = new Authenticator;
    $payload = $method->enable($this->user);

    expect($method->verify($this->user, (new Totp)->code($payload['secret'])))->toBeFalse();
});

it('does not verify an email challenge code before the method is confirmed', function () {
    Notification::fake();

    $method = new Email;
    $method->enable($this->user);

    $code = null;
    Notification::assertSentTo($this->user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    expect($method->verify($this->user, $code))->toBeFalse();
});

it('expires issued one-time codes after the configured lifetime', function () {
    config(['auth.two_factor.expire' => 10]);

    Notification::fake();

    $method = new Email;
    $method->enable($this->user);

    $code = null;
    Notification::assertSentTo($this->user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->travel(11)->minutes();

    expect($method->confirm($this->user, $code))->toBeFalse();
});
