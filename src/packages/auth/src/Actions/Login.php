<?php

namespace Redot\Auth\Actions;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\RateLimitsRequests;
use Redot\Auth\Concerns\RespondsWithJson;
use Redot\Auth\Contracts\LoginAction;

class Login implements LoginAction
{
    use QueriesUsers, RateLimitsRequests, RespondsWithJson;

    protected static array $identifiers = [];

    protected static array $rules = [];

    public static function identifiers(string $provider, array $identifiers): void
    {
        static::$identifiers[$provider] = $identifiers;
    }

    public static function validationRules(string $provider, array|Closure $rules): void
    {
        static::$rules[$provider] = $rules;
    }

    public static function getIdentifiers(string $provider): array
    {
        return static::$identifiers[$provider] ?? ['email'];
    }

    public function authenticate(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules($context));

        $this->ensureNotRateLimited($request, $context);

        $inputName = $context->identifierInputName();

        $user = $this->findUserByIdentifier((string) $request->input($inputName), $context);

        if (! $this->checkCredentials($user, $request)) {
            RateLimiter::hit($this->throttleKey($request, $context));

            throw ValidationException::withMessages([
                $inputName => __('auth.failed'),
            ]);
        }

        $this->touchLastLoginAt($user);
        RateLimiter::clear($this->throttleKey($request, $context));

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

    protected function checkCredentials(?Authenticatable $user, Request $request): bool
    {
        return $user !== null
            && Hash::check((string) $request->input('password'), (string) $user->password);
    }

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
