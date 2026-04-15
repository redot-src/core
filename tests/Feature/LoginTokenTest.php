<?php

use Redot\Models\LoginToken;

it('replaces an existing login token for the same email and guard', function () {
    $first = LoginToken::generate('jane@example.com', 'admins');
    $second = LoginToken::generate('jane@example.com', 'admins');

    expect(LoginToken::count())->toBe(1)
        ->and($second->id)->not->toBe($first->id)
        ->and(LoginToken::findByToken($second->token, 'admins')?->is($second))->toBeTrue();
});

it('only resolves non-expired tokens for the requested guard', function () {
    $valid = LoginToken::create([
        'email' => 'jane@example.com',
        'token' => str_repeat('a', 64),
        'code' => 'ABC123',
        'guard' => 'admins',
        'expires_at' => now()->addMinutes(5),
    ]);

    LoginToken::create([
        'email' => 'jane@example.com',
        'token' => str_repeat('b', 64),
        'code' => 'XYZ789',
        'guard' => 'admins',
        'expires_at' => now()->subMinute(),
    ]);

    expect(LoginToken::findByToken($valid->token, 'admins')?->is($valid))->toBeTrue()
        ->and(LoginToken::findByToken(str_repeat('b', 64), 'admins'))->toBeNull()
        ->and(LoginToken::findByToken($valid->token, 'users'))->toBeNull();
});
