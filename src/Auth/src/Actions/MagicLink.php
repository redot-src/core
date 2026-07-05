<?php

namespace Redot\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Redot\Auth\AuthContext;
use Redot\Auth\Concerns\QueriesUsers;
use Redot\Auth\Concerns\RateLimitsRequests;
use Redot\Auth\Contracts\MagicLinkAction;
use Redot\Models\LoginToken;
use Redot\Notifications\MagicLinkNotification;

class MagicLink implements MagicLinkAction
{
    use QueriesUsers, RateLimitsRequests;

    /**
     * The login token model class.
     */
    protected static string $loginTokenModel = LoginToken::class;

    /**
     * The notification class used to deliver magic links.
     */
    protected static string $notificationClass = MagicLinkNotification::class;

    /**
     * Override the login token model class.
     */
    public static function useLoginTokenModel(string $class): void
    {
        static::$loginTokenModel = $class;
    }

    /**
     * Override the magic link notification class.
     */
    public static function useNotificationClass(string $class): void
    {
        static::$notificationClass = $class;
    }

    /**
     * Email a magic link and one-time code to the user.
     */
    public function send(Request $request, AuthContext $context): RedirectResponse
    {
        $inputName = $context->identifierInputName();

        $request->validate([
            $inputName => ['required'],
        ]);

        $this->throttle(
            $request,
            $context,
            'magic-link',
            (int) config('auth.magic_link.throttle.max_attempts', 5),
            false,
        );

        $user = $this->findUserByIdentifier((string) $request->input($inputName), $context);

        if ($user === null) {
            $this->reject($request, $context, 'magic-link', (int) config('auth.magic_link.throttle.decay_minutes', 60) * 60);
        }

        $tokenModel = static::$loginTokenModel;
        $notificationClass = static::$notificationClass;

        $loginToken = $tokenModel::generate($user->email, $context->guard);
        $user->notify(new $notificationClass($loginToken, $context->routeName('magic-link-code.show')));

        $this->clear($request, $context, 'magic-link');

        return redirect()->route($context->routeName('magic-link-code.create'), [
            'email' => base64_encode((string) $user->email),
        ]);
    }

    /**
     * Sign the user in from an emailed link token.
     */
    public function verify(string $token, AuthContext $context): RedirectResponse
    {
        $loginToken = static::$loginTokenModel::findByToken($token, $context->guard);

        if ($loginToken === null) {
            return $this->redirectWithError(
                __('The login link is invalid or has expired.'),
                $context->routeName('magic-link.create'),
            );
        }

        return $this->authenticate($loginToken, $context);
    }

    /**
     * Show the one-time code entry screen.
     */
    public function view(Request $request, AuthContext $context): View|RedirectResponse
    {
        $email = base64_decode((string) $request->query('email'), true);

        if (! is_string($email) || $email === '') {
            return redirect()->route($context->routeName('magic-link.create'));
        }

        $exists = static::$loginTokenModel::where('email', $email)->forGuard($context->guard)->valid()->exists();

        if (! $exists) {
            return redirect()->route($context->routeName('magic-link.create'));
        }

        return view($context->views['magic-link-code'], [
            'email' => $email,
            'context' => $context,
        ]);
    }

    /**
     * Sign the user in from a submitted one-time code.
     */
    public function confirm(Request $request, AuthContext $context): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $loginToken = static::$loginTokenModel::findByCode(
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

    /**
     * Log the token's user into the guard and consume the token.
     */
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

    /**
     * Redirect to the given route with a flashed error message.
     */
    protected function redirectWithError(string|array $message, string $route, mixed $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with('error', $message);
    }
}
