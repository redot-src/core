<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Redot\Auth\RedotAuthManager;
use Redot\Notifications\MagicLinkNotification;
use Tests\Fixtures\Auth\NotifiableUser;

function createUsersTable(): void
{
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->timestamp('last_login_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
}

it('registers login, registration, logout, and email-verification routes for a configured guard by default', function () {
    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'register' => 'auth.register',
                'forgot-password' => 'auth.forgot-password',
                'reset-password' => 'auth.reset-password',
                'magic-link' => 'auth.magic-link',
            ],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('dashboard.login.store'))->toBeTrue()
        ->and(Route::has('dashboard.register.store'))->toBeTrue()
        ->and(Route::has('dashboard.logout'))->toBeTrue()
        ->and(Route::has('dashboard.verification.send'))->toBeTrue();
});

it('skips registering routes for explicitly disabled features', function () {
    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: ['login' => 'auth.login'],
            disable: ['register', 'magic-link', 'email-verification', 'lock-screen'],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    expect(Route::has('dashboard.login.store'))->toBeTrue()
        ->and(Route::has('dashboard.logout'))->toBeTrue()
        ->and(Route::has('dashboard.register.store'))->toBeFalse()
        ->and(Route::has('dashboard.verification.send'))->toBeFalse()
        ->and(Route::has('dashboard.unlock'))->toBeFalse();
});

it('throttles magic-link code confirmation attempts', function () {
    createUsersTable();

    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'register' => 'auth.register',
                'forgot-password' => 'auth.forgot-password',
                'reset-password' => 'auth.reset-password',
                'magic-link' => 'auth.magic-link',
            ],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    $route = Route::getRoutes()->getByName('dashboard.magic-link-code.store');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('guest:admins', 'throttle:6,1');

    foreach (range(1, 6) as $attempt) {
        $this->post('/magic-link/code', [
            'email' => 'admin@example.com',
            'code' => '000000',
        ])->assertRedirect();
    }

    $this->post('/magic-link/code', [
        'email' => 'admin@example.com',
        'code' => '000000',
    ])->assertStatus(429);
});

it('throttles magic-link send attempts', function () {
    createUsersTable();

    Route::name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'register' => 'auth.register',
                'forgot-password' => 'auth.forgot-password',
                'reset-password' => 'auth.reset-password',
                'magic-link' => 'auth.magic-link',
            ],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    foreach (range(1, 5) as $attempt) {
        $this->post('/magic-link', ['email' => 'unknown@example.com'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    $this->from('/magic-link')->post('/magic-link', ['email' => 'unknown@example.com'])
        ->assertInvalid(['email' => 'Too many attempts. Please try again in 1 hour.']);
});

it('clears the magic-link send throttle after a successful login', function () {
    Notification::fake();
    createUsersTable();

    config()->set('auth.providers.admins.model', NotifiableUser::class);

    Route::get('/home', fn () => 'home')->name('dashboard.index');

    Route::middleware('web')->name('dashboard.')->group(function () {
        app(RedotAuthManager::class)->routes(
            guard: 'admins',
            views: [
                'login' => 'auth.login',
                'register' => 'auth.register',
                'forgot-password' => 'auth.forgot-password',
                'reset-password' => 'auth.reset-password',
                'magic-link' => 'auth.magic-link',
            ],
            home: 'dashboard.index',
        );
    });

    Route::getRoutes()->refreshNameLookups();

    NotifiableUser::create(['email' => 'admin@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/magic-link', ['email' => 'admin@example.com'])->assertSessionHasNoErrors();
    }

    $this->from('/magic-link')->post('/magic-link', ['email' => 'admin@example.com'])
        ->assertInvalid(['email' => 'Too many attempts']);

    // Codes are hashed at rest, so grab the plaintext code from the sent notification.
    $code = null;

    Notification::assertSentTo(
        NotifiableUser::firstOrFail(),
        MagicLinkNotification::class,
        function (MagicLinkNotification $notification) use (&$code) {
            $code = $notification->loginToken->code;

            return true;
        },
    );

    $this->post('/magic-link/code', ['email' => 'admin@example.com', 'code' => $code])
        ->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticated('admins');

    auth('admins')->logout();

    $this->post('/magic-link', ['email' => 'admin@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard.magic-link-code.create', ['email' => base64_encode('admin@example.com')]));
});

it('throws when the requested guard is not configured', function () {
    app(RedotAuthManager::class)->routes('missing');
})->throws(InvalidArgumentException::class, 'Guard [missing] is not configured.');

it('throws when the guard provider does not point at a real model class', function () {
    config()->set('auth.guards.broken', ['driver' => 'session', 'provider' => 'broken']);
    config()->set('auth.providers.broken', ['driver' => 'eloquent', 'model' => 'App\\Does\\Not\\Exist']);

    app(RedotAuthManager::class)->routes('broken');
})->throws(InvalidArgumentException::class, 'Provider [broken] model is invalid.');
