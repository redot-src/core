<?php

namespace Redot\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\RateLimitsRequests;
use Redot\Auth\Contracts\MagicLinkAction;
use Redot\Models\LoginToken;
use Redot\Notifications\MagicLinkNotification;
use Redot\Traits\RespondAsApi;
use RuntimeException;

class MagicLink implements MagicLinkAction
{
    use QueriesUsers, RateLimitsRequests, RespondAsApi;

    protected static ?string $loginTokenModel = LoginToken::class;

    protected static ?string $notificationClass = MagicLinkNotification::class;

    public static function useLoginTokenModel(string $class): void
    {
        static::$loginTokenModel = $class;
    }

    public static function useNotificationClass(string $class): void
    {
        static::$notificationClass = $class;
    }

    public function send(Request $request, AuthContext $context): RedirectResponse
    {
        $inputName = $context->identifierInputName();

        $request->validate([
            $inputName => ['required'],
        ]);

        $this->ensureNotRateLimited(
            $request,
            $context,
            'magic-link',
            (int) config('auth.magic_link.throttle.max_attempts', 5),
            false,
        );

        $user = $this->findUserByIdentifier((string) $request->input($inputName), $context);

        if ($user === null) {
            $decaySeconds = (int) config('auth.magic_link.throttle.decay_minutes', 60) * 60;
            RateLimiter::hit($this->throttleKey($request, $context, 'magic-link'), $decaySeconds);

            throw ValidationException::withMessages([
                $inputName => __('auth.failed'),
            ]);
        }

        $tokenModel = $this->loginTokenModel();
        $notificationClass = $this->notificationClass();

        $loginToken = $tokenModel::generate($user->email, $context->guard);
        $user->notify(new $notificationClass($loginToken, $context->routeName('magic-link-code.show')));

        RateLimiter::clear($this->throttleKey($request, $context, 'magic-link'));

        return redirect()->route($context->routeName('magic-link-code.create'), [
            'email' => base64_encode((string) $user->email),
        ]);
    }

    public function verifyToken(string $token, AuthContext $context): RedirectResponse
    {
        $tokenModel = $this->loginTokenModel();
        $loginToken = $tokenModel::findByToken($token, $context->guard);

        if ($loginToken === null) {
            return $this->redirectWithError(
                __('The login link is invalid or has expired.'),
                $context->routeName('magic-link.create'),
            );
        }

        return $this->authenticate($loginToken, $context);
    }

    public function view(Request $request, AuthContext $context): View|RedirectResponse
    {
        $email = base64_decode((string) $request->query('email'), true);

        if (! is_string($email) || $email === '') {
            return redirect()->route($context->routeName('magic-link.create'));
        }

        $tokenModel = $this->loginTokenModel();
        $exists = $tokenModel::where('email', $email)->forGuard($context->guard)->valid()->exists();

        if (! $exists) {
            return redirect()->route($context->routeName('magic-link.create'));
        }

        return view($context->views['magic-link-code'], [
            'email' => $email,
            'context' => $context,
        ]);
    }

    public function verifyCode(Request $request, AuthContext $context): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $tokenModel = $this->loginTokenModel();
        $loginToken = $tokenModel::findByCode(
            $request->input('code'),
            $request->input('email'),
            $context->guard,
        );

        if ($loginToken === null) {
            throw ValidationException::withMessages([
                'code' => __('The code is invalid or has expired.'),
            ]);
        }

        return $this->authenticate($loginToken, $context);
    }

    protected function authenticate(object $loginToken, AuthContext $context): RedirectResponse
    {
        $user = $this->findUserByIdentifier((string) $loginToken->email, $context);

        if ($user === null) {
            $loginToken->delete();

            return $this->redirectWithError(
                __('auth.failed'),
                $context->routeName('magic-link.create'),
            );
        }

        $loginToken->delete();
        Auth::guard($context->guard)->login($user);
        $this->touchLastLoginAt($user);
        request()->session()->regenerate();

        return redirect()->intended($context->homeUrl());
    }

    protected function loginTokenModel(): string
    {
        if (static::$loginTokenModel === null) {
            throw new RuntimeException('Magic link login token model is not configured.');
        }

        return static::$loginTokenModel;
    }

    protected function notificationClass(): string
    {
        if (static::$notificationClass === null) {
            throw new RuntimeException('Magic link notification class is not configured.');
        }

        return static::$notificationClass;
    }

    protected function redirectWithError(string|array $message, string $route, mixed $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with('error', $message);
    }
}
