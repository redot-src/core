<?php

namespace Redot\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Redot\Auth\Authenticator;
use Redot\Auth\Concerns\ThrottlesAttempts;
use Redot\Auth\Features\MagicLink;
use Redot\Auth\Panel;
use Redot\Auth\Pipeline\LoginAttempt;
use Redot\Auth\Responses\MagicLinkSentResponse;
use Redot\Http\Controllers\Controller;
use Redot\Models\LoginToken;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class MagicLinkController extends Controller
{
    use ThrottlesAttempts;

    /**
     * Show the magic link request screen.
     */
    public function create(Panel $panel): View
    {
        return $panel->view('magic-link');
    }

    /**
     * Email a magic link and one-time code to the user.
     */
    public function store(Request $request, Panel $panel): Response
    {
        $feature = $panel->feature(MagicLink::class);
        $field = $panel->identifierInputName();

        $request->validate([
            $field => ['required', 'string'],
        ]);

        $identifier = (string) $request->input($field);
        $key = $this->throttleKey('magic-link', $identifier, $request->ip());

        if (RateLimiter::tooManyAttempts($key, $feature->getMaxAttempts())) {
            $this->throwThrottled($key, $field);
        }

        // Count every request, so known and unknown identifiers are throttled alike.
        RateLimiter::hit($key, $feature->getDecaySeconds());

        // Issue the link after the response is sent, so known and unknown identifiers take equally long.
        if ($user = $panel->findUser($identifier)) {
            defer(function () use ($user, $panel, $feature) {
                $token = $feature->getTokenModel()::generate($user->email, $panel->getGuard(), $feature->getExpiresIn());
                $url = route($panel->routeName('magic-link.verify'), ['token' => $token->token]);
                $notification = $feature->getNotification();

                $user->notify(new $notification($token, $url, $feature->getExpiresIn()));
            });
        }

        return $panel->make(MagicLinkSentResponse::class, ['identifier' => $identifier])->toResponse($panel);
    }

    /**
     * Sign the user in from an emailed link.
     */
    public function verify(Request $request, Panel $panel): Response
    {
        $token = $panel->feature(MagicLink::class)->getTokenModel()::findByToken((string) $request->route('token'), $panel->getGuard());

        if ($token === null) {
            return $this->error(__('The login link is invalid or has expired.'), $panel->routeName('magic-link.create'));
        }

        return $this->authenticate($request, $panel, $token);
    }

    /**
     * Show the one-time code screen.
     */
    public function code(Request $request, Panel $panel): View|RedirectResponse
    {
        $email = base64_decode((string) $request->query('email'), true);

        if (! is_string($email) || $email === '') {
            return redirect()->route($panel->routeName('magic-link.create'));
        }

        return $panel->view('magic-link-code', ['email' => $email]);
    }

    /**
     * Sign the user in from a submitted one-time code.
     */
    public function confirm(Request $request, Panel $panel): Response
    {
        $request->validate([
            'email' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        // The "email" input carries whatever identifier the link was requested with,
        // while tokens are always stored against the user's email.
        $identifier = (string) $request->input('email');
        $email = $panel->findUser($identifier)?->email ?? $identifier;

        $token = $panel->feature(MagicLink::class)->getTokenModel()::findByCode($request->input('code'), $email, $panel->getGuard());

        if ($token === null) {
            throw ValidationException::withMessages([
                'code' => __('The code is invalid or has expired.'),
            ]);
        }

        return $this->authenticate($request, $panel, $token, $identifier);
    }

    /**
     * Consume the token and log its user in.
     */
    protected function authenticate(Request $request, Panel $panel, LoginToken $token, ?string $identifier = null): Response
    {
        $token->delete();

        $user = $panel->query()->where('email', $token->email)->first();

        if ($user === null) {
            return $this->error(__('auth.failed'), $panel->routeName('magic-link.create'));
        }

        // Lift the request throttle, which was counted against the typed identifier.
        foreach (array_filter([$token->email, $identifier]) as $value) {
            RateLimiter::clear($this->throttleKey('magic-link', $value, $request->ip()));
        }

        $attempt = new LoginAttempt($request, $panel, identifier: $identifier ?? $token->email, user: $user);

        return $panel->make(Authenticator::class)->authenticated($attempt);
    }
}
