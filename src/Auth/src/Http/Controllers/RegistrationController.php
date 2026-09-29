<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\Authenticator;
use Redot\Auth\Contracts\CreatesUsers;
use Redot\Auth\Panel;
use Redot\Auth\Responses\RegisterResponse;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    /**
     * Show the registration screen.
     */
    public function create(Panel $panel): View
    {
        return $panel->view('register');
    }

    /**
     * Register a new user and sign them in.
     */
    public function store(Request $request, Panel $panel): Response
    {
        $creator = $panel->make(CreatesUsers::class);

        $user = $creator->create($request->validate($creator->rules($panel)), $panel);

        event(new Registered($user));

        $token = $panel->make(Authenticator::class)->login($request, $panel, $user);

        return $panel->make(RegisterResponse::class, ['user' => $user, 'token' => $token])->toResponse($panel);
    }
}
