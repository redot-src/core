<?php

namespace Redot\Auth\Actions;

use Closure;
use Illuminate\Auth\Events\Registered;
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

    protected static array $rules = [];

    protected static array $createUsing = [];

    public static function validationRules(string $provider, array|Closure $rules): void
    {
        static::$rules[$provider] = $rules;
    }

    public static function createUserUsing(string $provider, Closure $callback): void
    {
        static::$createUsing[$provider] = $callback;
    }

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

    protected function createUser(Request $request, AuthContext $context): Model
    {
        if (isset(static::$createUsing[$context->provider])) {
            return (static::$createUsing[$context->provider])($request, $context);
        }

        return $context->model::create(
            $request->only('email', 'password')
        );
    }
}
