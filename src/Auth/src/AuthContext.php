<?php

namespace Redot\Auth;

use Redot\Auth\Middleware\Locked;

class AuthContext
{
    /**
     * The features disabled for this context, keyed by feature name.
     */
    public readonly array $disabled;

    /**
     * Create a new auth context instance.
     */
    public function __construct(
        public readonly string $guard,
        public readonly string $provider,
        public readonly string $broker,
        public readonly string $model,
        public readonly ?\Closure $scope,
        public readonly bool $api,
        public readonly string $namePrefix,
        public readonly array $views,
        public readonly mixed $home,
        public readonly array $identifiers = ['email'],
        array $disable = [],
    ) {
        $this->disabled = $this->resolveDisabled($disable);
    }

    /**
     * Prefix the given route name with the context's name prefix.
     */
    public function routeName(string $name): string
    {
        return $this->namePrefix . $name;
    }

    /**
     * Determine whether the given feature is enabled for this context.
     */
    public function featureEnabled(string $feature): bool
    {
        return ! ($this->disabled[$feature] ?? false);
    }

    /**
     * Resolve the URL users are sent to after authenticating.
     */
    public function homeUrl(): string
    {
        if (is_callable($this->home)) {
            return call_user_func($this->home);
        }

        $route = is_string($this->home) ? $this->home : $this->routeName('index');

        return route($route);
    }

    /**
     * Get the request input name that carries the user identifier.
     */
    public function identifierInputName(): string
    {
        return count($this->identifiers) === 1 ? $this->identifiers[0] : 'identifier';
    }

    /**
     * Get the middleware stack for guest-only routes.
     */
    public function guest(): array
    {
        return ['guest:' . $this->guard];
    }

    /**
     * Get the middleware stack for authenticated routes.
     */
    public function auth(): array
    {
        $middleware = ['auth:' . $this->guard];

        if ($this->featureEnabled('lock-screen')) {
            $middleware[] = $this->lockedMiddleware();
        }

        return $middleware;
    }

    /**
     * Build the locked-session middleware definition for this guard.
     */
    public function lockedMiddleware(): string
    {
        return Locked::class . ':' . $this->guard . ',' . $this->routeName('unlock');
    }

    /**
     * Normalize the disable list, forcing off features the context cannot support.
     */
    protected function resolveDisabled(array $disable): array
    {
        $supported = [
            'register',
            'magic-link',
            'email-verification',
            'logout',
            'lock-screen',
        ];

        $disabled = [];

        foreach ($disable as $feature) {
            if (is_string($feature) && in_array($feature, $supported, true)) {
                $disabled[$feature] = true;
            }
        }

        if ($this->api) {
            $disabled['magic-link'] = true;
        }

        if ($this->api || ! isset($this->views['unlock'])) {
            $disabled['lock-screen'] = true;
        }

        return $disabled;
    }
}
