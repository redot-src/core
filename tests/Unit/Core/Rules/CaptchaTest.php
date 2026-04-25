<?php

use Illuminate\Support\Facades\Http;
use Redot\Models\Setting;
use Redot\Rules\Captcha;

it('passes captcha validation outside production', function () {
    expect((new Captcha)->passes('captcha', 'anything'))->toBeTrue();
});

it('verifies captcha tokens against cloudflare in production', function () {
    app()->detectEnvironment(fn () => 'production');
    Setting::set('cloudflare_turnstile_secret_key', 'secret');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
    ]);

    expect((new Captcha)->passes('captcha', 'token'))->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        && $request['secret'] === 'secret'
        && $request['response'] === 'token');
});

it('fails captcha validation in production without a secret', function () {
    app()->detectEnvironment(fn () => 'production');

    expect((new Captcha)->passes('captcha', 'token'))->toBeFalse();
});
