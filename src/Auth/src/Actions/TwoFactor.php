<?php

namespace Redot\Auth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\TwoFactorAuthenticatable;
use Redot\Auth\Contracts\TwoFactorAction;
use Redot\Auth\Methods\Authenticator;
use Redot\Auth\Methods\Email;
use Redot\Auth\Methods\TwoFactorMethod;
use Redot\Traits\RespondAsApi;

class TwoFactor implements TwoFactorAction
{
    use QueriesUsers, RespondAsApi;

    protected static array $methods = [
        Authenticator::class,
        Email::class,
    ];

    public static function registerMethod(string $class): void
    {
        static::$methods[] = $class;
    }

    /**
     * @return Collection<string, TwoFactorMethod>
     */
    public static function methods(): Collection
    {
        return collect(static::$methods)
            ->map(fn (string $class): TwoFactorMethod => app($class))
            ->keyBy(fn (TwoFactorMethod $method): string => $method->key());
    }

    public static function enabledMethods(?Authenticatable $user): Collection
    {
        if ($user === null || ! in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true)) {
            return collect();
        }

        return static::methods()->filter(fn (TwoFactorMethod $method): bool => $method->enabled($user));
    }

    public static function sessionKey(string $guard): string
    {
        return "auth.$guard.two_factor";
    }

    public function redirectToChallenge(Request $request, Authenticatable $user, AuthContext $context): RedirectResponse|JsonResponse
    {
        if ($context->api) {
            $token = Str::random(60);
            $expire = (int) config('auth.two_factor.expire', 10);

            Cache::put($this->challengeCacheKey($context, $token), [
                'id' => $user->getAuthIdentifier(),
                'attempts' => 0,
                'expires_at' => now()->addMinutes($expire)->getTimestamp(),
            ], now()->addMinutes($expire));

            return $this->respond([
                'two_factor' => true,
                'challenge_token' => $token,
            ]);
        }

        $request->session()->put(static::sessionKey($context->guard), [
            'id' => $user->getAuthIdentifier(),
            'remember' => $request->boolean('remember'),
            'attempts' => 0,
        ]);

        return redirect()->route($context->routeName('two-factor.challenge'));
    }

    public function challenge(Request $request, AuthContext $context): View|RedirectResponse
    {
        $user = $this->challengedUser($request, $context);

        if ($user === null) {
            return redirect()->route($context->routeName('login'));
        }

        return view($context->views['two-factor-challenge'], [
            'context' => $context,
            'methods' => static::enabledMethods($user),
        ]);
    }

    public function verify(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $state = $this->challengeState($request, $context);
        $user = $this->challengedUser($request, $context, $state);

        if ($user === null) {
            return $this->challengeExpired($context);
        }

        $request->validate([
            'code' => ['required_without:recovery_code', 'nullable', 'string'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
        ]);

        if (! $this->attempt($request, $user)) {
            return $this->recordFailedAttempt($request, $context, $state);
        }

        return $this->completeLogin($request, $user, $context, $state);
    }

    public function send(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse
    {
        $user = $this->challengedUser($request, $context);

        if ($user === null) {
            return $this->challengeExpired($context);
        }

        $instance = static::enabledMethods($user)->get($method);

        if ($instance === null || ! $instance->deliverable()) {
            abort(404);
        }

        $instance->send($user);

        return $context->api
            ? $this->respond()
            : back()->with('success', __('A verification code has been sent to you.'));
    }

    public function edit(Request $request, AuthContext $context): View|JsonResponse
    {
        $user = $this->currentUser($context);

        $methods = static::methods()->map(fn (TwoFactorMethod $method): array => [
            'enabled' => $method->enabled($user),
            'pending' => $method->pending($user),
            'deliverable' => $method->deliverable(),
        ]);

        if ($context->api) {
            return $this->respond(['methods' => $methods]);
        }

        return view($context->views['two-factor'], [
            'context' => $context,
            'user' => $user,
            'methods' => $methods,
        ]);
    }

    public function store(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse
    {
        $user = $this->currentUser($context);
        $instance = $this->method($method);

        if ($instance->enabled($user)) {
            return $context->api ? $this->respond() : back();
        }

        $payload = $instance->enable($user);

        if ($context->api || $request->expectsJson()) {
            return $this->respond($payload);
        }

        return back()->with('two_factor_setup', $method);
    }

    public function confirm(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse
    {
        $user = $this->currentUser($context);

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $this->method($method)->confirm($user, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('The provided two factor code is invalid.'),
            ]);
        }

        $codes = $user->recoveryCodes() === [] ? $user->generateRecoveryCodes() : null;

        if ($context->api) {
            return $this->respond($codes === null ? [] : ['recovery_codes' => $codes]);
        }

        return back()
            ->with('success', __('Two factor method has been enabled.'))
            ->with('two_factor_recovery_codes', $codes);
    }

    public function destroy(Request $request, AuthContext $context, string $method): RedirectResponse|JsonResponse
    {
        $user = $this->currentUser($context);
        $instance = $this->method($method);
        $enabled = $instance->enabled($user);

        if ($enabled) {
            $this->validatePassword($request, $context);
        }

        $instance->disable($user);

        if (static::enabledMethods($user)->isEmpty()) {
            $user->forgetRecoveryCodes();
        }

        $message = $enabled
            ? __('Two factor method has been disabled.')
            : __('Two factor setup has been cancelled.');

        return $context->api ? $this->respond() : back()->with('success', $message);
    }

    public function recoveryCodes(Request $request, AuthContext $context): RedirectResponse|JsonResponse
    {
        $user = $this->currentUser($context);
        $this->validatePassword($request, $context);

        if (static::enabledMethods($user)->isEmpty()) {
            abort(404);
        }

        $codes = $user->generateRecoveryCodes();

        return $context->api
            ? $this->respond(['recovery_codes' => $codes])
            : back()->with('success', __('Recovery codes have been regenerated.'))->with('two_factor_recovery_codes', $codes);
    }

    protected function attempt(Request $request, Authenticatable $user): bool
    {
        if ($request->filled('recovery_code')) {
            $code = trim((string) $request->input('recovery_code'));
            $match = collect($user->recoveryCodes())->first(fn (string $stored): bool => hash_equals($stored, $code));

            if ($match === null) {
                return false;
            }

            $user->replaceRecoveryCode($match);

            return true;
        }

        $code = (string) $request->input('code');

        return static::enabledMethods($user)->contains(fn (TwoFactorMethod $method): bool => $method->verify($user, $code));
    }

    protected function recordFailedAttempt(Request $request, AuthContext $context, array $state): RedirectResponse|JsonResponse
    {
        $state['attempts'] = ($state['attempts'] ?? 0) + 1;

        if ($state['attempts'] >= (int) config('auth.two_factor.max_attempts', 5)) {
            $this->forgetChallenge($request, $context);

            $message = __('Too many attempts. Please log in again.');

            return $context->api
                ? $this->fail($message, 429)
                : redirect()->route($context->routeName('login'))->with('error', $message);
        }

        $this->saveChallengeState($request, $context, $state);

        throw ValidationException::withMessages([
            'code' => __('The provided two factor code is invalid.'),
        ]);
    }

    protected function completeLogin(Request $request, Authenticatable $user, AuthContext $context, array $state): RedirectResponse|JsonResponse
    {
        $this->touchLastLoginAt($user);
        $this->forgetChallenge($request, $context);

        if ($context->api) {
            return $this->respond([
                'token' => $user->createToken('auth_token')->plainTextToken,
                'token_type' => 'Bearer',
            ]);
        }

        Auth::guard($context->guard)->login($user, (bool) ($state['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended($context->homeUrl());
    }

    protected function challengedUser(Request $request, AuthContext $context, ?array $state = null): ?Authenticatable
    {
        $state ??= $this->challengeState($request, $context);

        if ($state === null) {
            return null;
        }

        return $this->applyScope($context->model::query(), $context->scope)->find($state['id']);
    }

    protected function challengeState(Request $request, AuthContext $context): ?array
    {
        $state = $context->api
            ? Cache::get($this->challengeCacheKey($context, (string) $request->input('challenge_token')))
            : $request->session()->get(static::sessionKey($context->guard));

        return is_array($state) ? $state : null;
    }

    protected function saveChallengeState(Request $request, AuthContext $context, array $state): void
    {
        if ($context->api) {
            $remaining = max(1, (int) $state['expires_at'] - now()->getTimestamp());

            Cache::put($this->challengeCacheKey($context, (string) $request->input('challenge_token')), $state, $remaining);

            return;
        }

        $request->session()->put(static::sessionKey($context->guard), $state);
    }

    protected function forgetChallenge(Request $request, AuthContext $context): void
    {
        if ($context->api) {
            Cache::forget($this->challengeCacheKey($context, (string) $request->input('challenge_token')));

            return;
        }

        $request->session()->forget(static::sessionKey($context->guard));
    }

    protected function challengeExpired(AuthContext $context): RedirectResponse|JsonResponse
    {
        return $context->api
            ? $this->fail(__('The two factor challenge is invalid or has expired.'), 401)
            : redirect()->route($context->routeName('login'));
    }

    protected function challengeCacheKey(AuthContext $context, string $token): string
    {
        return 'two-factor:challenge:' . $context->guard . ':' . $token;
    }

    protected function currentUser(AuthContext $context): Authenticatable
    {
        return Auth::guard($context->guard)->user();
    }

    protected function method(string $key): TwoFactorMethod
    {
        return static::methods()->get($key) ?? abort(404);
    }

    protected function validatePassword(Request $request, AuthContext $context): void
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:' . $context->guard],
        ]);
    }
}
