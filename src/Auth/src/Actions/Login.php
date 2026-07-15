<?php

namespace Redot\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\IssuesApiTokens;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\RateLimitsRequests;
use Redot\Auth\Concerns\ResolvesValidationRules;
use Redot\Auth\Contracts\LoginAction;
use Redot\Traits\RespondAsApi;

class Login implements LoginAction
{
    use IssuesApiTokens, QueriesUsers, RateLimitsRequests, ResolvesValidationRules, RespondAsApi;

    /**
     * The identifier columns used to look up users, keyed by provider.
     */
    protected static array $identifiers = [];

    /**
     * Override the identifier columns used to look up users for the given provider.
     */
    public static function identifiers(string $provider, array $identifiers): void
    {
        static::$identifiers[$provider] = $identifiers;
    }

    /**
     * Get the identifier columns for the given provider.
     */
    public static function getIdentifiers(string $provider): array
    {
        return static::$identifiers[$provider] ?? ['email'];
    }

    /**
     * Authenticate the request, starting a session or issuing a bearer token.
     */
    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules($context));

        $this->throttle($request, $context);

        $inputName = $context->identifierInputName();

        $user = $this->findUserByIdentifier((string) $request->input($inputName), $context);

        if (! $this->checkCredentials($user, $request)) {
            $this->reject($request, $context);
        }

        $this->touchLastLoginAt($user);
        $this->clear($request, $context);

        if ($context->api) {
            return $this->issueToken($user);
        }

        Auth::guard($context->guard)->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($context->homeUrl());
    }

    /**
     * Verify the supplied password against the user's stored hash.
     */
    protected function checkCredentials(?Authenticatable $user, Request $request): bool
    {
        $hash = $user !== null ? (string) $user->password : $this->dummyHash();

        return Hash::check((string) $request->input('password'), $hash)
            && $user !== null;
    }

    /**
     * Get a hash used to equalize credential checks for missing users.
     *
     * Built with the configured hasher (driver and cost) so the missing-user
     * path costs the same hash verification as a real user.
     */
    protected function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('redot-dummy-password');
    }

    /**
     * Get the default validation rules for the login request.
     */
    protected function defaultRules(AuthContext $context): array
    {
        $inputName = $context->identifierInputName();

        return [
            $inputName => ['required'],
            'password' => ['required'],
        ];
    }
}
