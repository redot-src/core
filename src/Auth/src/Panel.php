<?php

namespace Redot\Auth;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use InvalidArgumentException;
use Redot\Auth\Features\Feature;
use Redot\Auth\Features\LockScreen;
use Redot\Auth\Http\Middleware\Locked;

class Panel
{
    /**
     * The guard users authenticate against.
     */
    protected ?string $guard = null;

    /**
     * The callback that narrows the user query.
     */
    protected ?Closure $scope = null;

    /**
     * The prefix used to resolve view names.
     */
    protected string $viewPrefix = 'auth';

    /**
     * The view names overridden per screen.
     */
    protected array $views = [];

    /**
     * The route name or callback that resolves the post-login URL.
     */
    protected string|Closure|null $home = null;

    /**
     * The columns users may sign in with.
     */
    protected array $identifiers = ['email'];

    /**
     * Whether the panel authenticates with bearer tokens instead of sessions.
     */
    protected bool $api = false;

    /**
     * The enabled features, keyed by class.
     *
     * @var array<class-string<Feature>, Feature>
     */
    protected array $features = [];

    /**
     * The panel's container bindings.
     */
    protected array $bindings = [];

    /**
     * The name prefix of the routes the panel was registered under.
     */
    protected string $routePrefix = '';

    /**
     * Create a new panel instance.
     */
    public function __construct(public readonly string $name) {}

    /**
     * Set the guard users authenticate against.
     */
    public function guard(string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Narrow the users allowed to authenticate.
     */
    public function scope(Closure $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Set the view prefix, or override specific views by screen name.
     */
    public function views(string|array $views): static
    {
        if (is_string($views)) {
            $this->viewPrefix = $views;
        } else {
            $this->views = array_merge($this->views, $views);
        }

        return $this;
    }

    /**
     * Set the route name or callback that resolves the post-login URL.
     */
    public function home(string|Closure $home): static
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Set the columns users may sign in with.
     */
    public function identifiers(array $identifiers): static
    {
        $this->identifiers = $identifiers;

        return $this;
    }

    /**
     * Authenticate with bearer tokens instead of sessions.
     */
    public function api(bool $api = true): static
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Enable the given features.
     */
    public function features(Feature ...$features): static
    {
        foreach ($features as $feature) {
            $this->features[$feature::class] = $feature;
        }

        return $this;
    }

    /**
     * Replace the implementation of the given class for this panel.
     */
    public function using(string $abstract, string|Closure $concrete): static
    {
        $this->bindings[$abstract] = $concrete;

        return $this;
    }

    /**
     * Get a copy of the panel bound to the given route name prefix.
     */
    public function withRoutePrefix(string $prefix): static
    {
        $panel = clone $this;
        $panel->routePrefix = $prefix;

        return $panel;
    }

    /**
     * Get the guard name.
     */
    public function getGuard(): string
    {
        if ($this->guard === null) {
            throw new InvalidArgumentException("Auth panel [$this->name] has no guard.");
        }

        if (! is_array(config('auth.guards.' . $this->guard))) {
            throw new InvalidArgumentException("Guard [$this->guard] is not configured.");
        }

        return $this->guard;
    }

    /**
     * Get the user provider name of the panel's guard.
     */
    public function provider(): string
    {
        $provider = config('auth.guards.' . $this->getGuard() . '.provider');

        if (! is_string($provider) || $provider === '') {
            throw new InvalidArgumentException("Guard [$this->guard] has no provider.");
        }

        return $provider;
    }

    /**
     * Get the user model class.
     *
     * @return class-string<Model&Authenticatable>
     */
    public function model(): string
    {
        $provider = $this->provider();
        $model = config('auth.providers.' . $provider . '.model');

        if (! is_string($model) || ! class_exists($model)) {
            throw new InvalidArgumentException("Provider [$provider] model is invalid.");
        }

        return $model;
    }

    /**
     * Get the password broker configured for the panel's provider.
     */
    public function broker(): string
    {
        $provider = $this->provider();

        foreach (config('auth.passwords', []) as $broker => $config) {
            if (($config['provider'] ?? null) === $provider) {
                return $broker;
            }
        }

        return $provider;
    }

    /**
     * Determine whether the panel authenticates with bearer tokens.
     */
    public function isApi(): bool
    {
        return $this->api;
    }

    /**
     * Get the columns users may sign in with.
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * Get the request input name that carries the user identifier.
     */
    public function identifierInputName(): string
    {
        return count($this->identifiers) === 1 ? $this->identifiers[0] : 'identifier';
    }

    /**
     * Get the enabled features.
     *
     * @return array<class-string<Feature>, Feature>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Determine whether the given feature is enabled.
     *
     * @param  class-string<Feature>  $feature
     */
    public function hasFeature(string $feature): bool
    {
        return $this->findFeature($feature) !== null;
    }

    /**
     * Get the given feature's configuration.
     *
     * @template T of Feature
     *
     * @param  class-string<T>  $feature
     * @return T
     */
    public function feature(string $feature): Feature
    {
        return $this->findFeature($feature) ?? throw new InvalidArgumentException("Feature [$feature] is not enabled on auth panel [$this->name].");
    }

    /**
     * Find the enabled feature of the given type, including subclasses.
     *
     * @param  class-string<Feature>  $feature
     */
    protected function findFeature(string $feature): ?Feature
    {
        foreach ($this->features as $instance) {
            if ($instance instanceof $feature) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Resolve the given class with its constructor parameters, preferring the panel's own binding over the container's.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $concrete = $this->bindings[$abstract] ?? $abstract;

        return $concrete instanceof Closure ? $concrete($this, $parameters) : app($concrete, $parameters);
    }

    /**
     * Start a user query, honouring the panel's scope.
     */
    public function query(): Builder
    {
        $query = $this->model()::query();

        if ($this->scope === null) {
            return $query;
        }

        $result = ($this->scope)($query);

        return $result instanceof Builder ? $result : $query;
    }

    /**
     * Find the user matching the given identifier value.
     */
    public function findUser(string $value): (Model&Authenticatable)|null
    {
        return $this->query()
            ->where(function (Builder $query) use ($value) {
                foreach ($this->identifiers as $column) {
                    $query->orWhere($column, $value);
                }
            })
            ->first();
    }

    /**
     * Prefix the given route name with the panel's route name prefix.
     */
    public function routeName(string $name): string
    {
        return $this->routePrefix . $name;
    }

    /**
     * Get the view name for the given screen.
     */
    public function viewName(string $screen): string
    {
        return $this->views[$screen] ?? $this->viewPrefix . '.' . $screen;
    }

    /**
     * Render the given screen.
     */
    public function view(string $screen, array $data = []): View
    {
        return view($this->viewName($screen), ['panel' => $this, ...$data]);
    }

    /**
     * Resolve the URL users are sent to after authenticating.
     */
    public function homeUrl(): string
    {
        if ($this->home instanceof Closure) {
            return ($this->home)($this);
        }

        return route($this->home ?? $this->routeName('index'));
    }

    /**
     * Get the middleware for guest-only routes.
     */
    public function guestMiddleware(): array
    {
        return ['guest:' . $this->getGuard()];
    }

    /**
     * Get the middleware for authenticated routes.
     */
    public function authMiddleware(): array
    {
        $middleware = ['auth:' . $this->getGuard()];

        if ($this->hasFeature(LockScreen::class)) {
            $middleware[] = $this->lockedMiddleware();
        }

        return $middleware;
    }

    /**
     * Get the middleware that sends locked sessions to the unlock screen.
     */
    public function lockedMiddleware(): string
    {
        return Locked::using($this->getGuard());
    }
}
