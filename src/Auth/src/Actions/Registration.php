<?php

namespace Redot\Auth\Actions;

use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Redot\Auth\AuthContext;
use Redot\Auth\Contracts\RegistrationAction;
use Redot\Traits\RespondAsApi;

class Registration implements RegistrationAction
{
    use RespondAsApi;

    /**
     * Custom registration validation rules, keyed by provider.
     */
    protected static array $rules = [];

    /**
     * Custom user creation callbacks, keyed by provider.
     */
    protected static array $createUsing = [];

    /**
     * Override the registration validation rules for the given provider.
     */
    public static function validationRules(string $provider, array|Closure $rules): void
    {
        static::$rules[$provider] = $rules;
    }

    /**
     * Override how users are created for the given provider.
     */
    public static function createUserUsing(string $provider, Closure $callback): void
    {
        static::$createUsing[$provider] = $callback;
    }

    /**
     * Register a new user, starting a session or issuing a bearer token.
     */
    public function register(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules($context));

        $user = $this->createUser($request, $context);

        event(new Registered($user));

        if ($context->api) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->respond(
                code: 201,
                payload: [
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            );
        }

        Auth::guard($context->guard)->login($user);

        return redirect()->intended($context->homeUrl());
    }

    /**
     * Resolve the validation rules for the registration request.
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

        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . $context->model],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Create the user record for the request.
     */
    protected function createUser(Request $request, AuthContext $context): Model&Authenticatable
    {
        if (isset(static::$createUsing[$context->provider])) {
            return (static::$createUsing[$context->provider])($request, $context);
        }

        return $context->model::create(
            $request->only('email', 'password')
        );
    }
}
