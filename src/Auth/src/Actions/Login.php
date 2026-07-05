<?php

namespace Redot\Auth\Actions;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\RateLimitsRequests;
use Redot\Auth\Contracts\LoginAction;
use Redot\Traits\RespondAsApi;

class Login implements LoginAction
{
    use QueriesUsers, RateLimitsRequests, RespondAsApi;

    /**
     * The identifier columns used to look up users, keyed by provider.
     */
    protected static array $identifiers = [];

    /**
     * Custom login validation rules, keyed by provider.
     */
    protected static array $rules = [];

    /**
     * Override the identifier columns used to look up users for the given provider.
     */
    public static function identifiers(string $provider, array $identifiers): void
    {
        static::$identifiers[$provider] = $identifiers;
    }

    /**
     * Override the login validation rules for the given provider.
     */
    public static function validationRules(string $provider, array|Closure $rules): void
    {
        static::$rules[$provider] = $rules;
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
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->respond([
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
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
        return $user !== null
            && Hash::check((string) $request->input('password'), (string) $user->password);
    }

    /**
     * Resolve the validation rules for the login request.
     */
    protected function rules(AuthContext $context): array
    {
        $rules = static::$rules[$context->provider] ?? null;

        if ($rules instanceof Closure) {
            return ($rules)($context);
        }

        if (is_array($rules)) {
            return $rules;
        }

        $inputName = $context->identifierInputName();

        return [
            $inputName => ['required'],
            'password' => ['required'],
        ];
    }
}
