<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

it('records the authenticated owner polymorphically per session guard', function () {
    $admin = SessionAdmin::create(['name' => 'admin']);
    $member = SessionMember::create(['name' => 'member']);

    writeSession('admin-session', 'session_admins', $admin);
    writeSession('member-session', 'session_members', $member);
    writeSession('guest-session');

    expect(DB::table('sessions')->where('id', 'admin-session')->first())
        ->user_type->toBe(SessionAdmin::class)
        ->user_id->toBe($admin->id);

    expect(DB::table('sessions')->where('id', 'member-session')->first())
        ->user_type->toBe(SessionMember::class)
        ->user_id->toBe($member->id);

    $guest = DB::table('sessions')->where('id', 'guest-session')->first();
    expect($guest->user_type)->toBeNull()
        ->and($guest->user_id)->toBeNull();
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
        ->and(DB::table('sessions')->whereNull('user_type')->count())->toBe(1);
});
