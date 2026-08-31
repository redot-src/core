<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Redot\Session\DatabaseSessionHandler;
use Tests\Fixtures\Auth\SessionAdmin;
use Tests\Fixtures\Auth\SessionMember;

beforeEach(function () {
    foreach (['session_admins', 'session_members'] as $table) {
        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    config()->set('auth.guards.session_admins', ['driver' => 'session', 'provider' => 'session_admins']);
    config()->set('auth.guards.session_members', ['driver' => 'session', 'provider' => 'session_members']);
    config()->set('auth.guards.token', ['driver' => 'sanctum', 'provider' => 'session_members']);
    config()->set('auth.providers.session_admins', ['driver' => 'eloquent', 'model' => SessionAdmin::class]);
    config()->set('auth.providers.session_members', ['driver' => 'eloquent', 'model' => SessionMember::class]);
});

function handler(): DatabaseSessionHandler
{
    return new DatabaseSessionHandler(DB::connection(), 'sessions', 120, app());
}

function writeSession(string $id, ?string $guard = null, $user = null): void
{
    if ($guard) {
        Auth::guard($guard)->setUser($user);
    }

    handler()->write($id, serialize(['_token' => $id]));

    if ($guard) {
        Auth::guard($guard)->forgetUser();
    }
}

it('registers the polymorphic handler as the database session driver', function () {
    expect(Session::driver('database')->getHandler())
        ->toBeInstanceOf(DatabaseSessionHandler::class);
});

it('records the authenticated owner polymorphically per session guard', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    writeSession('admin-session', 'session_admins', $admin);
    writeSession('member-session', 'session_members', $member);
    writeSession('guest-session');

    expect(DB::table('session_authentications')->where('session_id', 'admin-session')->first())
        ->guard->toBe('session_admins')
        ->user_type->toBe(SessionAdmin::class)
        ->user_id->toBe($admin->id);

    expect(DB::table('session_authentications')->where('session_id', 'member-session')->first())
        ->guard->toBe('session_members')
        ->user_type->toBe(SessionMember::class)
        ->user_id->toBe($member->id);

    expect(DB::table('sessions')->where('id', 'guest-session')->exists())->toBeTrue()
        ->and(DB::table('session_authentications')->where('session_id', 'guest-session')->exists())->toBeFalse();
});

it('associates one browser session with every authenticated session guard', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    Auth::guard('session_admins')->setUser($admin);
    Auth::guard('session_members')->setUser($member);

    handler()->write('shared-session', serialize(['_token' => 'shared-session']));

    expect(DB::table('session_authentications')->where('session_id', 'shared-session')->count())->toBe(2)
        ->and(DB::table('session_authentications')
            ->where('session_id', 'shared-session')
            ->where('guard', 'session_admins')
            ->where('user_type', SessionAdmin::class)
            ->where('user_id', $admin->id)
            ->exists())->toBeTrue()
        ->and(DB::table('session_authentications')
            ->where('session_id', 'shared-session')
            ->where('guard', 'session_members')
            ->where('user_type', SessionMember::class)
            ->where('user_id', $member->id)
            ->exists())->toBeTrue()
        ->and($admin->sessions()->sole()->getKey())->toBe('shared-session')
        ->and($member->sessions()->sole()->getKey())->toBe('shared-session');
});

it('exposes sessions per entity through the HasSessions trait', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    writeSession('admin-1', 'session_admins', $admin);
    writeSession('admin-2', 'session_admins', $admin);
    writeSession('member-1', 'session_members', $member);

    expect($admin->sessions()->count())->toBe(2)
        ->and($member->sessions()->count())->toBe(1);
});

it('revokes only the owning entity sessions when logging out everywhere', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    writeSession('admin-1', 'session_admins', $admin);
    writeSession('admin-2', 'session_admins', $admin);
    writeSession('member-1', 'session_members', $member);
    writeSession('guest', null);

    $deleted = $admin->logoutAllSessions();

    expect($deleted)->toBe(2)
        ->and($admin->sessions()->count())->toBe(0)
        ->and($member->sessions()->count())->toBe(1)
        ->and(DB::table('sessions')->where('id', 'guest')->exists())->toBeTrue()
        ->and(DB::table('session_authentications')->where('session_id', 'guest')->exists())->toBeFalse();
});

it('revokes a shared browser session for every associated guard', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    Auth::guard('session_admins')->setUser($admin);
    Auth::guard('session_members')->setUser($member);

    handler()->write('shared-session', serialize(['_token' => 'shared-session']));

    Auth::guard('session_admins')->forgetUser();
    Auth::guard('session_members')->forgetUser();
    writeSession('member-session', 'session_members', $member);

    $deleted = $admin->logoutAllSessions();

    expect($deleted)->toBe(1)
        ->and(DB::table('sessions')->where('id', 'shared-session')->exists())->toBeFalse()
        ->and(DB::table('session_authentications')->where('session_id', 'shared-session')->exists())->toBeFalse()
        ->and($member->sessions()->pluck('sessions.id')->all())->toBe(['member-session']);
});
