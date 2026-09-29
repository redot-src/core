<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Redot\Auth\Authenticator;
use Redot\Auth\Features\Login;
use Redot\Auth\Panel;
use Redot\Auth\Pipeline\LoginAttempt;
use Redot\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * Show the login screen.
     */
    public function create(Panel $panel): View
    {
        return $panel->view('login');
    }

    /**
     * Run the credentials through the login pipeline.
     */
    public function store(Request $request, Panel $panel): Response
    {
        $login = $panel->feature(Login::class);

        $request->validate($login->getRules($panel));

        return $panel->make(Authenticator::class)->attempt(LoginAttempt::fromRequest($request, $panel));
    }
}
