<?php

namespace Redot\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Redot\Traits\RespondAsApi;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use RespondAsApi;
    use ValidatesRequests;

    /**
     * Redirect with a successful creation message.
     */
    public function created(string $resource, ?string $route = null, mixed $parameters = [])
    {
        $message = __(':resource has been created.', ['resource' => $resource]);

        if ($route === null) {
            return back()->with('success', $message);
        }

        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with a successful update message.
     */
    public function updated(string $resource, ?string $route = null, mixed $parameters = [])
    {
        $message = __(':resource has been updated.', ['resource' => $resource]);

        if ($route === null) {
            return back()->with('success', $message);
        }

        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with a successful deletion message.
     */
    public function deleted(string $resource, ?string $route = null, mixed $parameters = [])
    {
        $message = __(':resource has been deleted.', ['resource' => $resource]);

        if ($route === null) {
            return back()->with('success', $message);
        }

        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with a successful restoration message.
     */
    public function restored(string $resource, ?string $route = null, mixed $parameters = [])
    {
        $message = __(':resource has been restored.', ['resource' => $resource]);

        if ($route === null) {
            return back()->with('success', $message);
        }

        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with a success message.
     */
    public function success(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        if ($route === null) {
            return back()->with('success', $message);
        }

        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Redirect with an error message.
     */
    public function error(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        if ($route === null) {
            return back()->with('error', $message);
        }

        return redirect()->route($route, $parameters)->with('error', $message);
    }

    /**
     * Redirect with a warning message.
     */
    public function warning(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        if ($route === null) {
            return back()->with('warning', $message);
        }

        return redirect()->route($route, $parameters)->with('warning', $message);
    }

    /**
     * Redirect with an info message.
     */
    public function info(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        if ($route === null) {
            return back()->with('info', $message);
        }

        return redirect()->route($route, $parameters)->with('info', $message);
    }
}
