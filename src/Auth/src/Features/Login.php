<?php

namespace Redot\Auth\Features;

use Closure;
use Illuminate\Support\Facades\Route;
use Redot\Auth\Http\Controllers\LoginController;
use Redot\Auth\Panel;
use Redot\Auth\Pipeline\AttemptToAuthenticate;
use Redot\Auth\Pipeline\EnsureLoginIsNotThrottled;

class Login extends Feature
{
    /**
     * The validation rules, or a callback that adjusts the defaults.
     */
    protected array|Closure|null $rules = null;

    /**
     * The credential steps, or a callback that adjusts the defaults.
     */
    protected array|Closure|null $steps = null;

    /**
     * The failed attempts allowed before the login is throttled.
     */
    protected int $maxAttempts = 5;

    /**
     * The seconds a failed attempt counts against the throttle.
     */
    protected int $decaySeconds = 60;

    /**
     * Replace the validation rules, or adjust the defaults with a callback.
     */
    public function rules(array|Closure $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Replace the credential steps, or adjust the defaults with a callback.
     */
    public function steps(array|Closure $steps): static
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * Set how many failed attempts are allowed per decay window.
     */
    public function throttle(int $maxAttempts, int $decaySeconds = 60): static
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;

        return $this;
    }

    /**
     * Get the validation rules for the given panel.
     */
    public function getRules(Panel $panel): array
    {
        $rules = [
            $panel->identifierInputName() => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        if ($this->rules instanceof Closure) {
            return ($this->rules)($rules, $panel);
        }

        return $this->rules ?? $rules;
    }

    /**
     * Get the steps that verify the submitted credentials.
     */
    public function getSteps(Panel $panel): array
    {
        $steps = [
            EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
        ];

        if ($this->steps instanceof Closure) {
            return ($this->steps)($steps, $panel);
        }

        return $this->steps ?? $steps;
    }

    /**
     * Get the failed attempts allowed before the login is throttled.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Get the seconds a failed attempt counts against the throttle.
     */
    public function getDecaySeconds(): int
    {
        return $this->decaySeconds;
    }

    /**
     * Register the login screen and sign-in endpoint.
     */
    public function routes(Panel $panel): void
    {
        Route::middleware($panel->guestMiddleware())->group(function () use ($panel) {
            if (! $panel->isApi()) {
                Route::get('login', [LoginController::class, 'create'])->name('login');
            }

            Route::post('login', [LoginController::class, 'store'])->name('login.store');
        });
    }
}
