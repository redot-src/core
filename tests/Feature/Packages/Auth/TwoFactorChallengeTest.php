<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Redot\Auth\Actions\TwoFactor;
use Redot\Auth\RedotAuthManager;
use Redot\Auth\Support\Totp;
use Redot\Notifications\TwoFactorCodeNotification;
use Tests\Fixtures\TwoFactorUser;

beforeEach(function () {
    Schema::create('two_factor_users', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('password');
        $table->string('remember_token')->nullable();
        $table->timestamp('last_login_at')->nullable();
        $table->text('two_factor_secret')->nullable();
        $table->text('two_factor_recovery_codes')->nullable();
        $table->timestamp('two_factor_confirmed_at')->nullable();
        $table->timestamp('two_factor_email_confirmed_at')->nullable();
    });

    Schema::create('personal_access_tokens', function (Blueprint $table) {
        $table->id();
        $table->morphs('tokenable');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });

    config()->set('auth.providers.admins.model', TwoFactorUser::class);
    config()->set('auth.guards.admins-api', ['driver' => 'sanctum', 'provider' => 'admins']);

    Route::middleware('web')->name('dashboard.')->group(function () {
        // Plain home route so redirect()->intended() has a target that renders no view.
        Route::get('home', fn (): string => 'ok')->name('index');

        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'two-factor-challenge' => 'auth.two-factor-challenge',
            ],
            home: 'dashboard.index',
        );
    });

    Route::prefix('api')->name('api.')->group(function () {
        app(RedotAuthManager::class)->routes(guard: 'admins-api');
    });

    Route::getRoutes()->refreshNameLookups();

    $this->secret = (new Totp)->generateSecret();
});

// Creates the canonical challenge user: an armed authenticator user unless overridden.
function challenge_user(array $attributes = []): TwoFactorUser
{
    return TwoFactorUser::create([
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
        'two_factor_secret' => encrypt(test()->secret),
        'two_factor_confirmed_at' => now(),
        ...$attributes,
    ]);
}

// Posts the web login route with the given user's credentials.
function challenge_login(TwoFactorUser $user, array $payload = []): TestResponse
{
    return test()->post(route('dashboard.login.store'), [
        'email' => $user->email,
        'password' => 'password',
        ...$payload,
    ]);
}

it('logs a user without two factor straight in', function () {
    $user = challenge_user([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    challenge_login($user)->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('redirects to the challenge instead of logging in when a method is enabled', function () {
    $user = challenge_user();

    challenge_login($user)->assertRedirect(route('dashboard.two-factor.challenge'));

    $this->assertGuest('admins');

    expect($user->fresh()->last_login_at)->toBeNull();
});

it('redirects to login when no challenge is pending', function () {
    $this->get(route('dashboard.two-factor.challenge'))
        ->assertRedirect(route('dashboard.login'));
});

it('completes login with a valid authenticator code', function () {
    $user = challenge_user();

    challenge_login($user);

    $this->post(route('dashboard.two-factor.challenge.store'), [
        'code' => (new Totp)->code($this->secret),
    ])->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');

    expect(session()->has(TwoFactor::sessionKey('admins')))->toBeFalse()
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('carries remember-me through the challenge', function () {
    challenge_login(challenge_user(), ['remember' => '1']);

    $this->post(route('dashboard.two-factor.challenge.store'), [
        'code' => (new Totp)->code($this->secret),
    ])->assertCookie(Auth::guard('admins')->getRecallerName());

    $this->assertAuthenticated('admins');
});

it('rejects an invalid code and keeps the challenge alive', function () {
    challenge_login(challenge_user());

    $this->from(route('dashboard.two-factor.challenge'))
        ->post(route('dashboard.two-factor.challenge.store'), ['code' => '000000'])
        ->assertRedirect(route('dashboard.two-factor.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest('admins');

    $this->post(route('dashboard.two-factor.challenge.store'), [
        'code' => (new Totp)->code($this->secret),
    ])->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');
});

it('invalidates the challenge after the configured max attempts', function () {
    config(['auth.two_factor.max_attempts' => 2]);

    challenge_login(challenge_user());

    $this->post(route('dashboard.two-factor.challenge.store'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->post(route('dashboard.two-factor.challenge.store'), ['code' => '000000'])
        ->assertRedirect(route('dashboard.login'));

    $this->assertGuest('admins');

    // The challenge state is gone: even a valid code is bounced back to login.
    $this->post(route('dashboard.two-factor.challenge.store'), [
        'code' => (new Totp)->code($this->secret),
    ])->assertRedirect(route('dashboard.login'));

    $this->assertGuest('admins');
});

it('signs in with a recovery code and rotates only the used code', function () {
    $user = challenge_user();

    $codes = $user->generateRecoveryCodes();
    $used = $codes[0];

    challenge_login($user);

    $this->post(route('dashboard.two-factor.challenge.store'), ['recovery_code' => $used])
        ->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');

    $fresh = $user->fresh()->recoveryCodes();

    expect($fresh)->toHaveCount(8)
        ->and($fresh)->not->toContain($used)
        ->and(array_slice($fresh, 1))->toBe(array_slice($codes, 1));

    // The used code is spent: it does not pass a fresh challenge.
    $this->flushSession();
    $this->app['auth']->forgetGuards();

    challenge_login($user);

    $this->post(route('dashboard.two-factor.challenge.store'), ['recovery_code' => $used])
        ->assertSessionHasErrors('code');

    $this->assertGuest('admins');
});

it('sends the email code automatically when redirecting to the challenge', function () {
    Notification::fake();

    $user = challenge_user([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_email_confirmed_at' => now(),
    ]);

    challenge_login($user)->assertRedirect(route('dashboard.two-factor.challenge'));

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    // The auto-sent code passes the challenge without an explicit send request.
    $this->post(route('dashboard.two-factor.challenge.store'), ['code' => $code])
        ->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');
});

it('does not auto-send codes when only non-deliverable methods are enabled', function () {
    Notification::fake();

    challenge_login(challenge_user())->assertRedirect(route('dashboard.two-factor.challenge'));

    Notification::assertNothingSent();
});

it('emails a challenge code on demand and accepts it exactly once', function () {
    Notification::fake();

    $user = challenge_user([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_email_confirmed_at' => now(),
    ]);

    challenge_login($user);

    $this->post(route('dashboard.two-factor.challenge.send', 'email'))
        ->assertSessionHas('success');

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->post(route('dashboard.two-factor.challenge.store'), ['code' => $code])
        ->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');

    // The code was consumed on success: it does not pass a fresh challenge.
    $this->flushSession();
    $this->app['auth']->forgetGuards();

    challenge_login($user);

    $this->post(route('dashboard.two-factor.challenge.store'), ['code' => $code])
        ->assertSessionHasErrors('code');

    $this->assertGuest('admins');
});

it('returns 404 from send for non-deliverable, non-enabled and unknown methods', function () {
    challenge_login(challenge_user());

    // The authenticator method never delivers codes.
    $this->post(route('dashboard.two-factor.challenge.send', 'totp'))->assertNotFound();

    // Email delivers codes but is not enabled for this user.
    $this->post(route('dashboard.two-factor.challenge.send', 'email'))->assertNotFound();

    // Unknown method keys are rejected.
    $this->post(route('dashboard.two-factor.challenge.send', 'sms'))->assertNotFound();
});

it('returns a challenge token instead of a bearer token for api guards', function () {
    challenge_user();

    $this->postJson(route('api.login.store'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('payload.two_factor', true)
        ->assertJsonStructure(['payload' => ['two_factor', 'challenge_token']])
        ->assertJsonMissingPath('payload.token');
});

it('sends the email code alongside the challenge token for api guards', function () {
    Notification::fake();

    $user = challenge_user([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_email_confirmed_at' => now(),
    ]);

    $challengeToken = $this->postJson(route('api.login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->json('payload.challenge_token');

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => $challengeToken,
        'code' => $code,
    ])
        ->assertOk()
        ->assertJsonStructure(['payload' => ['token', 'token_type']]);
});

it('exchanges a challenge token and code for a bearer token', function () {
    challenge_user();

    $challengeToken = $this->postJson(route('api.login.store'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->json('payload.challenge_token');

    $token = $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => $challengeToken,
        'code' => (new Totp)->code($this->secret),
    ])
        ->assertOk()
        ->assertJsonStructure(['payload' => ['token', 'token_type']])
        ->assertJsonPath('payload.token_type', 'Bearer')
        ->json('payload.token');

    $this->withToken($token)
        ->getJson(route('api.two-factor.edit'))
        ->assertOk();

    // The challenge token is single-use: replaying it is rejected.
    $this->app['auth']->forgetGuards();

    $this->withoutToken()
        ->postJson(route('api.two-factor.challenge.store'), [
            'challenge_token' => $challengeToken,
            'code' => (new Totp)->code($this->secret),
        ])->assertUnauthorized();
});

it('rejects an invalid or expired challenge token', function () {
    challenge_user();

    $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => 'bogus-token',
        'code' => '000000',
    ])->assertUnauthorized();

    $challengeToken = $this->postJson(route('api.login.store'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->json('payload.challenge_token');

    $this->travel(11)->minutes();

    $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => $challengeToken,
        'code' => (new Totp)->code($this->secret),
    ])->assertUnauthorized();
});

it('tracks failed api attempts across requests and locks the challenge', function () {
    challenge_user();

    $challengeToken = $this->postJson(route('api.login.store'), [
        'email' => 'user@example.com',
        'password' => 'password',
    ])->json('payload.challenge_token');

    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('api.two-factor.challenge.store'), [
            'challenge_token' => $challengeToken,
            'code' => '000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => $challengeToken,
        'code' => '000000',
    ])->assertStatus(429);

    // The locked challenge token is dead: even a valid code is rejected.
    $this->postJson(route('api.two-factor.challenge.store'), [
        'challenge_token' => $challengeToken,
        'code' => (new Totp)->code($this->secret),
    ])->assertUnauthorized();
});
