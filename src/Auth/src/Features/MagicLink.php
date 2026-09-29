<?php

namespace Redot\Auth\Features;

use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\MagicLinkController;
use Redot\Auth\Panel;
use Redot\Models\LoginToken;
use Redot\Notifications\MagicLinkNotification;

class MagicLink extends Feature
{
    /**
     * The login token model class.
     *
     * @var class-string<LoginToken>
     */
    protected string $tokenModel = LoginToken::class;

    /**
     * The notification class used to deliver magic links.
     *
     * @var class-string<MagicLinkNotification>
     */
    protected string $notification = MagicLinkNotification::class;

    /**
     * The minutes a magic link stays valid.
     */
    protected ?int $expiresIn = null;

    /**
     * The links a user may request per decay window.
     */
    protected ?int $maxAttempts = null;

    /**
     * The seconds a link request counts against the throttle.
     */
    protected ?int $decaySeconds = null;

    /**
     * Use the given login token model.
     */
    public function tokenModel(string $class): static
    {
        $this->tokenModel = $class;

        return $this;
    }

    /**
     * Deliver magic links with the given notification.
     */
    public function notification(string $class): static
    {
        $this->notification = $class;

        return $this;
    }

    /**
     * Set the minutes a magic link stays valid.
     */
    public function expiresIn(int $minutes): static
    {
        $this->expiresIn = $minutes;

        return $this;
    }

    /**
     * Set how many links a user may request per decay window.
     */
    public function throttle(int $maxAttempts, int $decaySeconds = 3600): static
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;

        return $this;
    }

    /**
     * Get the login token model class.
     *
     * @return class-string<LoginToken>
     */
    public function getTokenModel(): string
    {
        return $this->tokenModel;
    }

    /**
     * Get the notification class used to deliver magic links.
     *
     * @return class-string<MagicLinkNotification>
     */
    public function getNotification(): string
    {
        return $this->notification;
    }

    /**
     * Get the minutes a magic link stays valid.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn ?? (int) config('auth.magic_link.expire', 15);
    }

    /**
     * Get the links a user may request per decay window.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts ?? (int) config('auth.magic_link.throttle.max_attempts', 5);
    }

    /**
     * Get the seconds a link request counts against the throttle.
     */
    public function getDecaySeconds(): int
    {
        return $this->decaySeconds ?? (int) config('auth.magic_link.throttle.decay_minutes', 60) * 60;
    }

    /**
     * Magic links sign users into a session, so API panels cannot use them.
     */
    public function supportsApi(): bool
    {
        return false;
    }

    /**
     * Register the magic link and one-time code routes.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->guestMiddleware())->controller(MagicLinkController::class)->group(function () {
            Route::get('magic-link', 'create')->name('magic-link.create');
            Route::post('magic-link', 'store')->name('magic-link.store');
            Route::get('magic-link/verify/{token}', 'verify')->name('magic-link.verify');
            Route::get('magic-link/code', 'code')->name('magic-link-code.create');
            Route::post('magic-link/code', 'confirm')->middleware('throttle:6,1')->name('magic-link-code.store');
        });
    }
}
