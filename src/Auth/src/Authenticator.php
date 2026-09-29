<?php

namespace Redot\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Redot\Auth\Concerns\RecordsLastLogin;
use Redot\Auth\Features\Login;
use Redot\Auth\Pipeline\CompleteLogin;
use Redot\Auth\Pipeline\LoginAttempt;
use Redot\Auth\Responses\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

class Authenticator
{
    use RecordsLastLogin;

    /**
     * Verify the attempt's credentials, then sign the user in.
     */
    public function attempt(LoginAttempt $attempt): Response
    {
        $credentialSteps = $attempt->panel->feature(Login::class)->getSteps($attempt->panel);

        return $this->run($attempt, [...$credentialSteps, ...$this->verifiedSteps($attempt->panel)]);
    }

    /**
     * Sign in a user whose identity was proven another way, e.g. by a magic link.
     */
    public function authenticated(LoginAttempt $attempt): Response
    {
        return $this->run($attempt, $this->verifiedSteps($attempt->panel));
    }

    /**
     * Finish signing in once every verified step has passed, e.g. after a two-factor challenge.
     */
    public function complete(LoginAttempt $attempt): Response
    {
        return $this->run($attempt, [CompleteLogin::class]);
    }

    /**
     * Start a session for the user, or issue a bearer token on API panels.
     */
    public function login(Request $request, Panel $panel, Model&Authenticatable $user, bool $remember = false): ?string
    {
        $this->recordLastLogin($user);

        if ($panel->isApi()) {
            return $user->createToken('auth_token')->plainTextToken;
        }

        Auth::guard($panel->getGuard())->login($user, $remember);
        $request->session()->regenerate();

        return null;
    }

    /**
     * Get the steps run once the user's identity is verified, as adjusted by the panel's features.
     */
    protected function verifiedSteps(Panel $panel): array
    {
        $steps = [CompleteLogin::class];

        foreach ($panel->getFeatures() as $feature) {
            $steps = $feature->verifiedSteps($steps);
        }

        return $steps;
    }

    /**
     * Send the attempt through the given steps and respond with the signed-in user.
     */
    protected function run(LoginAttempt $attempt, array $steps): Response
    {
        return app(Pipeline::class)
            ->send($attempt)
            ->through($steps)
            ->then(fn (LoginAttempt $attempt) => $attempt->panel->make(LoginResponse::class, ['user' => $attempt->user, 'token' => $attempt->token])->toResponse($attempt->panel));
    }
}
