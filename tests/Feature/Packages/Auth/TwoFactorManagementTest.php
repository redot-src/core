<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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

    // Consuming apps configure the guest redirect in bootstrap; mirror that here.
    Authenticate::redirectUsing(fn () => route('dashboard.login'));

    $this->secret = (new Totp)->generateSecret();
});

afterEach(function () {
    // redirectUsing() sets a process-wide static; reset it so it cannot leak into later test files.
    (new ReflectionProperty(Authenticate::class, 'redirectToCallback'))->setValue(null, null);
});

// Creates a two-factor capable user with no method enabled unless overridden.
function management_user(array $attributes = []): TwoFactorUser
{
    return TwoFactorUser::create([
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
        ...$attributes,
    ]);
}

it('starts authenticator setup with a pending secret', function () {
    $user = management_user();

    $this->from(route('dashboard.index'))
        ->actingAs($user, 'admins')
        ->post(route('dashboard.two-factor.store', 'totp'))
        ->assertRedirect(route('dashboard.index'));

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->twoFactorSecret())->toBeString()->not->toBeEmpty();

    // The api variant returns the secret and its QR code URL.
    $api = management_user(['email' => 'api@example.com']);

    $this->withToken($api->createToken('auth_token')->plainTextToken)
        ->postJson(route('api.two-factor.store', 'totp'))
        ->assertOk()
        ->assertJsonStructure(['payload' => ['secret', 'qr_code_url']]);
});

it('is a no-op when the method is already enabled', function () {
    $user = management_user([
        'two_factor_secret' => encrypt($this->secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $original = $user->two_factor_secret;

    $this->from(route('dashboard.index'))
        ->actingAs($user, 'admins')
        ->post(route('dashboard.two-factor.store', 'totp'))
        ->assertRedirect(route('dashboard.index'));

    $user->refresh();

    expect($user->two_factor_secret)->toBe($original)
        ->and($user->two_factor_confirmed_at)->not->toBeNull();
});

it('confirms a method and issues recovery codes exactly once', function () {
    Notification::fake();

    $user = management_user();

    $this->actingAs($user, 'admins');

    // The first confirmed method generates the recovery codes.
    $this->post(route('dashboard.two-factor.store', 'totp'));

    $this->post(route('dashboard.two-factor.confirm', 'totp'), [
        'code' => (new Totp)->code($user->fresh()->twoFactorSecret()),
    ])->assertSessionHas('two_factor_recovery_codes');

    expect(session('two_factor_recovery_codes'))->toHaveCount(8)
        ->and($user->fresh()->recoveryCodes())->toHaveCount(8);

    // A second confirmed method does not flash new codes.
    $this->post(route('dashboard.two-factor.store', 'email'));

    $code = null;
    Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->post(route('dashboard.two-factor.confirm', 'email'), ['code' => $code])
        ->assertSessionMissing('two_factor_recovery_codes');

    // The api variant returns the codes in the payload.
    $api = management_user(['email' => 'api@example.com']);

    $token = $api->createToken('auth_token')->plainTextToken;

    $secret = $this->withToken($token)
        ->postJson(route('api.two-factor.store', 'totp'))
        ->json('payload.secret');

    $this->withToken($token)
        ->postJson(route('api.two-factor.confirm', 'totp'), ['code' => (new Totp)->code($secret)])
        ->assertOk()
        ->assertJsonCount(8, 'payload.recovery_codes');
});

it('rejects an invalid confirmation code', function () {
    $user = management_user();

    $this->actingAs($user, 'admins');

    $this->post(route('dashboard.two-factor.store', 'totp'));

    $this->post(route('dashboard.two-factor.confirm', 'totp'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

it('requires the current password to disable an enabled method', function () {
    $user = management_user([
        'two_factor_secret' => encrypt($this->secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user, 'admins');

    $this->delete(route('dashboard.two-factor.destroy', 'totp'))
        ->assertSessionHasErrors('password');

    $this->delete(route('dashboard.two-factor.destroy', 'totp'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->two_factor_secret)->not->toBeNull();

    $this->delete(route('dashboard.two-factor.destroy', 'totp'), ['password' => 'password'])
        ->assertSessionHas('success');

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

it('cancels a pending setup without a password', function () {
    $user = management_user();

    $this->actingAs($user, 'admins');

    $this->post(route('dashboard.two-factor.store', 'totp'));

    expect($user->fresh()->two_factor_secret)->not->toBeNull();

    $this->delete(route('dashboard.two-factor.destroy', 'totp'))
        ->assertSessionHas('success');

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

it('forgets recovery codes only when the last method is disabled', function () {
    $user = management_user([
        'two_factor_secret' => encrypt($this->secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_email_confirmed_at' => now(),
    ]);

    $user->generateRecoveryCodes();

    $this->actingAs($user, 'admins');

    $this->delete(route('dashboard.two-factor.destroy', 'email'), ['password' => 'password']);

    expect($user->fresh()->recoveryCodes())->toHaveCount(8);

    $this->delete(route('dashboard.two-factor.destroy', 'totp'), ['password' => 'password']);

    expect($user->fresh()->two_factor_recovery_codes)->toBeNull();
});

it('regenerates recovery codes with the current password and 404s when none enabled', function () {
    $user = management_user([
        'two_factor_secret' => encrypt($this->secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $old = $user->generateRecoveryCodes();

    $this->actingAs($user, 'admins');

    $this->post(route('dashboard.two-factor.recovery-codes.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->recoveryCodes())->toBe($old);

    $this->post(route('dashboard.two-factor.recovery-codes.store'), ['password' => 'password'])
        ->assertSessionHas('two_factor_recovery_codes');

    $fresh = $user->fresh()->recoveryCodes();

    expect($fresh)->toHaveCount(8)
        ->and($fresh)->not->toBe($old);

    // Without any enabled method there are no codes to regenerate.
    $bare = management_user(['email' => 'bare@example.com']);

    $this->actingAs($bare, 'admins')
        ->post(route('dashboard.two-factor.recovery-codes.store'), ['password' => 'password'])
        ->assertNotFound();
});

it('reports enabled, pending and deliverable state per method', function () {
    $user = management_user();

    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withToken($token)
        ->getJson(route('api.two-factor.edit'))
        ->assertOk()
        ->assertJsonPath('payload.methods.totp', ['enabled' => false, 'pending' => false, 'deliverable' => false])
        ->assertJsonPath('payload.methods.email', ['enabled' => false, 'pending' => false, 'deliverable' => true]);

    $secret = $this->withToken($token)
        ->postJson(route('api.two-factor.store', 'totp'))
        ->json('payload.secret');

    $this->withToken($token)
        ->getJson(route('api.two-factor.edit'))
        ->assertJsonPath('payload.methods.totp', ['enabled' => false, 'pending' => true, 'deliverable' => false]);

    $this->withToken($token)
        ->postJson(route('api.two-factor.confirm', 'totp'), ['code' => (new Totp)->code($secret)]);

    $this->withToken($token)
        ->getJson(route('api.two-factor.edit'))
        ->assertJsonPath('payload.methods.totp', ['enabled' => true, 'pending' => false, 'deliverable' => false]);
});

it('returns 404 for an unknown method key and redirects guests to login', function () {
    $user = management_user();

    $this->actingAs($user, 'admins');

    $this->post(route('dashboard.two-factor.store', 'sms'))->assertNotFound();
    $this->post(route('dashboard.two-factor.confirm', 'sms'), ['code' => '000000'])->assertNotFound();
    $this->delete(route('dashboard.two-factor.destroy', 'sms'))->assertNotFound();

    // Guests are sent to the login screen.
    $this->flushSession();
    $this->app['auth']->forgetGuards();

    $this->post(route('dashboard.two-factor.store', 'totp'))
        ->assertRedirect(route('dashboard.login'));
});
