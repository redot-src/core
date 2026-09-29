<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Redot\Auth\Authenticator;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Facades\RedotAuth;
use Redot\Auth\Features\EmailVerification;
use Redot\Auth\Features\Feature;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Features\Login;
use Redot\Auth\Features\Logout;
use Redot\Auth\Features\MagicLink;
use Redot\Auth\Features\PasswordReset;
use Redot\Auth\Features\Registration;
use Redot\Auth\Http\Middleware\Locked;
use Redot\Auth\Panel;
use Redot\Auth\Pipeline\AttemptToAuthenticate;
use Redot\Auth\Pipeline\EnsureLoginIsNotThrottled;
use Redot\Auth\Pipeline\LoginAttempt;
use Redot\Auth\Responses\MagicLinkSentResponse;
use Redot\Auth\Responses\UnlockedResponse;
use Redot\Models\LoginToken;
use Redot\Notifications\MagicLinkNotification;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Auth\ApiUser;
use Tests\Fixtures\Auth\NotifiableUser;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->boolean('active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    config()->set('auth.providers.admins.model', NotifiableUser::class);
    config()->set('auth.guards.admins-api', ['driver' => 'sanctum', 'provider' => 'admins']);

    Route::get('/home', fn () => 'home')->name('dashboard.index');
});

function challenge_feature(): Feature
{
    return new class extends Feature
    {
        public function verifiedSteps(array $steps): array
        {
            return [fn (LoginAttempt $attempt, Closure $next) => response('challenge: ' . $attempt->user->email), ...$steps];
        }

        public function routes(Panel $panel): void
        {
            Route::post('two-factor', function (Request $request, Panel $panel) {
                $user = $panel->findUser('admin@example.com');
                $attempt = new LoginAttempt($request, $panel, identifier: 'admin@example.com', remember: true, user: $user);

                return $panel->make(Authenticator::class)->complete($attempt);
            });
        }
    };
}

function register_panel(string $name = 'dashboard', string $prefix = 'dashboard.'): void
{
    Route::middleware('web')->name($prefix)->group(fn () => RedotAuth::routes($name));
    Route::getRoutes()->refreshNameLookups();
}

it('registers the routes of enabled features only', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(
        Login::make(),
        Logout::make(),
    );

    register_panel();

    expect(Route::has('dashboard.login'))->toBeTrue()
        ->and(Route::has('dashboard.login.store'))->toBeTrue()
        ->and(Route::has('dashboard.logout'))->toBeTrue()
        ->and(Route::has('dashboard.register.store'))->toBeFalse()
        ->and(Route::has('dashboard.magic-link.store'))->toBeFalse()
        ->and(Route::has('dashboard.unlock'))->toBeFalse();
});

it('skips screen routes on api panels', function () {
    RedotAuth::panel('api')->guard('admins-api')->api()->features(
        Login::make(),
        PasswordReset::make(),
    );

    register_panel('api', 'api.');

    expect(Route::has('api.login.store'))->toBeTrue()
        ->and(Route::has('api.login'))->toBeFalse()
        ->and(Route::has('api.password.email'))->toBeTrue()
        ->and(Route::has('api.password.request'))->toBeFalse();
});

it('rejects session-only features on api panels', function () {
    RedotAuth::panel('api')->guard('admins-api')->api()->features(MagicLink::make());

    register_panel('api', 'api.');
})->throws(InvalidArgumentException::class, 'is not supported by API panel [api]');

it('throws when routes are registered for an undefined panel', function () {
    RedotAuth::routes('missing');
})->throws(InvalidArgumentException::class, 'Auth panel [missing] is not defined.');

it('rejects token guards on panels not marked as api', function () {
    RedotAuth::panel('api')->guard('admins-api')->features(Login::make());

    register_panel('api', 'api.');
})->throws(InvalidArgumentException::class, 'Guard [admins-api] cannot start sessions, mark auth panel [api] as api().');

it('cannot resolve a panel outside of a panel route', function () {
    app(Panel::class);
})->throws(InvalidArgumentException::class, 'The current route does not belong to an auth panel.');

it('finds features by their parent class', function () {
    $login = new class extends Login {};

    RedotAuth::panel('dashboard')->guard('admins')->features($login);

    register_panel();

    NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertRedirect('/home');
});

it('keeps working after the routes are cached', function () {
    RedotAuth::panel('dashboard')
        ->guard('admins')
        ->scope(fn ($query) => $query->where('active', true))
        ->features(Login::make(), Logout::make());

    register_panel();

    foreach (Route::getRoutes() as $route) {
        $route->prepareForSerialization();
    }

    Route::setCompiledRoutes(unserialize(serialize(Route::getRoutes()->compile())));

    NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);
    NotifiableUser::create(['email' => 'inactive@example.com', 'password' => Hash::make('secret'), 'active' => false]);

    $this->post('/login', ['email' => 'inactive@example.com', 'password' => 'secret'])->assertInvalid('email');
    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertRedirect('/home');

    $this->assertAuthenticated('admins');
});

it('registers every auth route against a controller', function () {
    RedotAuth::panel('dashboard')->guard('admins')->views('auth')->features(
        Login::make(),
        Registration::make(),
        PasswordReset::make(),
        MagicLink::make(),
        EmailVerification::make(),
        Logout::make(),
        LockScreen::make(),
    );

    register_panel();

    $uses = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'dashboard.') && $route->getName() !== 'dashboard.index')
        ->map(fn ($route) => $route->getAction('uses'));

    expect($uses)->not->toBeEmpty()
        ->each->toBeString();
});

it('runs custom credential steps', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(
        Login::make()->steps(fn (array $steps) => [
            ...$steps,
            fn (LoginAttempt $attempt, Closure $next) => response('verified: ' . $attempt->user->email),
        ]),
    );

    register_panel();

    NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertSee('verified: admin@example.com');

    $this->assertGuest('admins');
});

it('passes the default rules to the rules callback', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(
        Login::make()->rules(fn (array $rules) => [...$rules, 'captcha' => ['required']]),
    );

    register_panel();

    $this->post('/login', ['password' => 'secret'])->assertInvalid(['email', 'captcha']);
});

it('pauses logins at feature steps, and completes them afterwards', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(Login::make(), challenge_feature());

    register_panel();

    NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertSee('challenge: admin@example.com');
    $this->assertGuest('admins');

    $this->post('/two-factor')->assertRedirect('/home');
    $this->assertAuthenticated('admins');

    expect(NotifiableUser::firstOrFail()->remember_token)->not->toBeNull();
});

it('keeps feature steps when the credential steps are replaced', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(
        Login::make()->steps([EnsureLoginIsNotThrottled::class, AttemptToAuthenticate::class]),
        challenge_feature(),
    );

    register_panel();

    NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertSee('challenge: admin@example.com');
});

it('signs magic-link users in by the email the token was issued to', function () {
    Schema::table('users', fn (Blueprint $table) => $table->string('username')->nullable());

    RedotAuth::panel('dashboard')->guard('admins')->identifiers(['email', 'username'])->features(MagicLink::make());

    register_panel();

    NotifiableUser::create(['email' => 'other@example.com', 'username' => 'admin@example.com']);
    $owner = NotifiableUser::create(['email' => 'admin@example.com', 'username' => 'owner']);
    $token = LoginToken::generate('admin@example.com', 'admins');

    $this->get('/magic-link/verify/' . $token->token)->assertRedirect('/home');

    expect(auth('admins')->id())->toBe($owner->id);
});

it('runs feature steps for magic-link sign-ins too', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(MagicLink::make(), challenge_feature());

    register_panel();

    NotifiableUser::create(['email' => 'admin@example.com']);
    $token = LoginToken::generate('admin@example.com', 'admins');

    $this->get('/magic-link/verify/' . $token->token)->assertSee('challenge: admin@example.com');
    $this->assertGuest('admins');
});

it('upgrades outdated password hashes on login', function (bool $enabled) {
    config()->set('hashing.rehash_on_login', $enabled);

    RedotAuth::panel('dashboard')->guard('admins')->features(Login::make());

    register_panel();

    $outdated = Hash::make('secret', ['rounds' => 4]);
    NotifiableUser::create(['email' => 'admin@example.com', 'password' => $outdated]);

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'secret'])->assertRedirect('/home');

    $password = NotifiableUser::firstOrFail()->password;

    expect($password === $outdated)->toBe(! $enabled)
        ->and(Hash::check('secret', $password))->toBeTrue()
        ->and(Hash::needsRehash($password))->toBe(! $enabled);
})->with(['enabled' => true, 'disabled' => false]);

it('sends the password reset link after the response', function () {
    Notification::fake();
    config()->set('auth.passwords.admins', ['provider' => 'admins', 'table' => 'password_reset_tokens', 'expire' => 60]);

    Schema::create('password_reset_tokens', function (Blueprint $table) {
        $table->string('email')->primary();
        $table->string('token');
        $table->timestamp('created_at')->nullable();
    });

    RedotAuth::panel('dashboard')->guard('admins')->features(PasswordReset::make());

    register_panel();

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/forgot-password', ['email' => 'unknown@example.com'])->assertSessionHas('success');
    Notification::assertNothingSent();

    $this->post('/forgot-password', ['email' => 'admin@example.com'])->assertSessionHas('success');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('throttles failed logins using the feature limits', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(Login::make()->throttle(2));

    register_panel();

    foreach (range(1, 2) as $attempt) {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertInvalid(['email' => __('auth.failed')]);
    }

    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
        ->assertInvalid(['email' => 'Too many attempts']);
});

it('creates users with the panel\'s user creator', function () {
    $creator = new class implements CreatesUsers
    {
        public function rules(Panel $panel): array
        {
            return ['email' => ['required', 'email']];
        }

        public function create(array $input, Panel $panel): Model&Authenticatable
        {
            return $panel->model()::create([...$input, 'password' => Hash::make('generated')]);
        }
    };

    RedotAuth::panel('dashboard')
        ->guard('admins')
        ->using(CreatesUsers::class, fn () => $creator)
        ->features(Registration::make());

    register_panel();

    $this->post('/register', ['email' => 'new@example.com'])->assertRedirect('/home');

    expect(Hash::check('generated', NotifiableUser::firstOrFail()->password))->toBeTrue();
    $this->assertAuthenticated('admins');
});

it('throttles magic-link code confirmation attempts', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(MagicLink::make());

    register_panel();

    expect(Route::getRoutes()->getByName('dashboard.magic-link-code.store')->gatherMiddleware())
        ->toContain('guest:admins', 'throttle:6,1');

    foreach (range(1, 6) as $attempt) {
        $this->post('/magic-link/code', ['email' => 'admin@example.com', 'code' => '000000'])->assertRedirect();
    }

    $this->post('/magic-link/code', ['email' => 'admin@example.com', 'code' => '000000'])->assertStatus(429);
});

it('throttles magic-link requests for unknown identifiers too', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(MagicLink::make()->throttle(3, 3600));

    register_panel();

    foreach (range(1, 3) as $attempt) {
        $this->post('/magic-link', ['email' => 'unknown@example.com'])->assertSessionHasNoErrors();
    }

    $this->post('/magic-link', ['email' => 'unknown@example.com'])
        ->assertInvalid(['email' => 'Too many attempts. Please try again in 1 hour.']);
});

it('falls back to the auth config for magic-link settings the panel leaves unset', function () {
    config()->set('auth.magic_link', ['expire' => 30, 'throttle' => ['max_attempts' => 2, 'decay_minutes' => 10]]);

    $configured = MagicLink::make();
    $overridden = MagicLink::make()->expiresIn(5)->throttle(9, 120);

    expect([$configured->getExpiresIn(), $configured->getMaxAttempts(), $configured->getDecaySeconds()])->toBe([30, 2, 600])
        ->and([$overridden->getExpiresIn(), $overridden->getMaxAttempts(), $overridden->getDecaySeconds()])->toBe([5, 9, 120]);

    config()->set('auth.magic_link', null);

    expect([$configured->getExpiresIn(), $configured->getMaxAttempts(), $configured->getDecaySeconds()])->toBe([15, 5, 3600]);
});

it('signs in with a magic-link code and clears the request throttle', function () {
    Notification::fake();

    RedotAuth::panel('dashboard')->guard('admins')->features(MagicLink::make()->expiresIn(30));

    register_panel();

    $user = NotifiableUser::create(['email' => 'admin@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/magic-link', ['email' => 'admin@example.com'])->assertSessionHasNoErrors();
    }

    $this->post('/magic-link', ['email' => 'admin@example.com'])->assertInvalid(['email' => 'Too many attempts']);

    // Codes are hashed at rest, so grab the plaintext code from the sent notification.
    $code = null;

    Notification::assertSentTo($user, MagicLinkNotification::class, function (MagicLinkNotification $notification) use (&$code) {
        $code = $notification->loginToken->code;

        return $notification->expiresIn === 30
            && $notification->url === route('dashboard.magic-link.verify', ['token' => $notification->loginToken->token]);
    });

    $this->post('/magic-link/code', ['email' => 'admin@example.com', 'code' => $code])->assertRedirect('/home');

    $this->assertAuthenticated('admins');

    auth('admins')->logout();

    $this->post('/magic-link', ['email' => 'admin@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard.magic-link-code.create', ['email' => base64_encode('admin@example.com')]));
});

it('locks the session and the protected middleware groups until the password is re-entered', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make()->protect('web'), Logout::make());
    RedotAuth::protectLockedGroups();

    register_panel();

    Route::middleware(['web', 'auth:admins'])->get('/secret', fn () => 'secret');

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->actingAs($user, 'admins')->post('/lock')->assertRedirect('/unlock');
    $this->get('/secret')->assertRedirect('/unlock');

    $this->post('/unlock', ['password' => 'wrong'])->assertInvalid('password');
    $this->post('/unlock', ['password' => 'secret'])->assertRedirect();
    $this->get('/secret')->assertOk();

    $this->post('/lock');
    $this->post('/logout')->assertRedirect('/home');
    $this->assertGuest('admins');
});

it('throttles unlock attempts', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make());

    register_panel();

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->actingAs($user, 'admins')->post('/lock');

    foreach (range(1, 6) as $attempt) {
        $this->post('/unlock', ['password' => 'wrong'])->assertInvalid('password');
    }

    $this->post('/unlock', ['password' => 'wrong'])->assertStatus(429);
});

it('issues and revokes bearer tokens on api panels', function () {
    $this->artisan('migrate', ['--path' => dirname(__DIR__, 4) . '/vendor/laravel/sanctum/database/migrations', '--realpath' => true]);
    config()->set('auth.providers.admins.model', ApiUser::class);

    RedotAuth::panel('api')->guard('admins-api')->api()->features(
        Login::make(),
        Registration::make(),
        Logout::make(),
    );

    register_panel('api', 'api.');

    $this->postJson('/register', ['email' => 'new@example.com', 'password' => 'Secret-123', 'password_confirmation' => 'Secret-123'])
        ->assertCreated()
        ->assertJsonPath('payload.token_type', 'Bearer');

    $token = $this->postJson('/login', ['email' => 'new@example.com', 'password' => 'Secret-123'])
        ->assertOk()
        ->json('payload.token');

    $this->withToken($token)->deleteJson('/logout')->assertOk();

    expect(ApiUser::firstOrFail()->tokens()->count())->toBe(1);
});

it('protects every listed middleware group on the application kernel', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make()->protect('web', 'api'));
    RedotAuth::protectLockedGroups();

    $locked = Locked::using('admins');

    expect(Route::getMiddlewareGroups()['web'])->toContain($locked)
        ->and(Route::getMiddlewareGroups()['api'])->toContain($locked)
        ->and(app(Kernel::class)->getMiddlewareGroups()['web'])->toContain($locked);
});

it('redirects to the unlock route of the panel that locked the session', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make()->protect('web'));
    RedotAuth::protectLockedGroups();

    Route::middleware('web')->prefix('admin')->name('admin.')->group(fn () => RedotAuth::routes('dashboard'));
    Route::middleware(['web', 'auth:admins'])->get('/secret', fn () => 'secret');
    Route::getRoutes()->refreshNameLookups();

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->actingAs($user, 'admins')->post('/admin/lock')->assertRedirect('/admin/unlock');
    $this->get('/secret')->assertRedirect('/admin/unlock');
});

it('drops stale locks instead of trapping the user in redirects', function (array $session) {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make()->protect('web'));
    RedotAuth::protectLockedGroups();

    register_panel();

    Route::middleware('web')->get('/open', fn () => 'open');

    $this->withSession($session)->get('/open')->assertOk()->assertSessionMissing('auth.admins.locked');
})->with([
    'signed out' => [['auth.admins.locked' => 'dashboard.unlock']],
    'unknown unlock route' => [['auth.admins.locked' => 'removed.unlock']],
]);

it('keeps the home url query when confirming a verified email', function () {
    Schema::table('users', fn (Blueprint $table) => $table->timestamp('email_verified_at')->nullable());

    RedotAuth::panel('dashboard')->guard('admins')->home(fn () => url('/home?tab=profile'))->features(EmailVerification::make());

    register_panel();

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'email_verified_at' => now()]);
    $url = URL::signedRoute('dashboard.verification.verify', ['id' => $user->id, 'hash' => sha1('admin@example.com')]);

    $this->actingAs($user, 'admins')->get($url)->assertRedirect(url('/home?tab=profile&verified=1'));
});

it('lets a panel replace a single response', function () {
    $magicLinkSent = new class('') extends MagicLinkSentResponse
    {
        protected function web(Panel $panel): Response
        {
            return redirect('/check-your-inbox?for=' . $this->identifier);
        }
    };

    $unlocked = new class extends UnlockedResponse
    {
        protected function web(Panel $panel): Response
        {
            return redirect('/welcome-back');
        }
    };

    RedotAuth::panel('dashboard')
        ->guard('admins')
        ->using(MagicLinkSentResponse::class, $magicLinkSent::class)
        ->using(UnlockedResponse::class, $unlocked::class)
        ->features(MagicLink::make(), LockScreen::make());

    register_panel();

    $user = NotifiableUser::create(['email' => 'admin@example.com', 'password' => Hash::make('secret')]);

    $this->post('/magic-link', ['email' => 'admin@example.com'])->assertRedirect('/check-your-inbox?for=admin@example.com');

    $this->actingAs($user, 'admins')->post('/lock');
    $this->post('/unlock', ['password' => 'secret'])->assertRedirect('/welcome-back');
});

it('refuses to protect an undefined middleware group', function () {
    RedotAuth::panel('dashboard')->guard('admins')->features(LockScreen::make()->protect('missing'));

    RedotAuth::protectLockedGroups();
})->throws(InvalidArgumentException::class, 'The [missing] middleware group has not been defined.');
