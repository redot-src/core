<?php

namespace Redot\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Js;
use Illuminate\Support\Traits\Macroable;
use Redot\Traits\RespondAsApi;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use Macroable;
    use RespondAsApi;
    use ValidatesRequests;

    /**
     * Message templates for resource action redirects.
     */
    protected static array $messages = [
        'created' => ':resource has been created.',
        'updated' => ':resource has been updated.',
        'deleted' => ':resource has been deleted.',
        'restored' => ':resource has been restored.',
    ];

    /**
     * Redirect with a successful creation message.
     */
    public function created(string $resource, ?string $route = null, mixed $parameters = [])
    {
        return $this->success(__(static::$messages['created'], ['resource' => $resource]), $route, $parameters);
    }

    /**
     * Redirect with a successful update message.
     */
    public function updated(string $resource, ?string $route = null, mixed $parameters = [])
    {
        return $this->success(__(static::$messages['updated'], ['resource' => $resource]), $route, $parameters);
    }

    /**
     * Redirect with a successful deletion message.
     */
    public function deleted(string $resource, ?string $route = null, mixed $parameters = [])
    {
        return $this->success(__(static::$messages['deleted'], ['resource' => $resource]), $route, $parameters);
    }

    /**
     * Redirect with a successful restoration message.
     */
    public function restored(string $resource, ?string $route = null, mixed $parameters = [])
    {
        return $this->success(__(static::$messages['restored'], ['resource' => $resource]), $route, $parameters);
    }

    /**
     * Redirect with a success message.
     */
    public function success(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        return $this->flash('success', $message, $route, $parameters);
    }

    /**
     * Redirect with an error message.
     */
    public function error(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        return $this->flash('error', $message, $route, $parameters);
    }

    /**
     * Redirect with a warning message.
     */
    public function warning(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        return $this->flash('warning', $message, $route, $parameters);
    }

    /**
     * Redirect with an info message.
     */
    public function info(string|array $message, ?string $route = null, mixed $parameters = [])
    {
        return $this->flash('info', $message, $route, $parameters);
    }

    /**
     * Redirect with a flashed message of the given type.
     */
    public function flash(string $type, string|array $message, ?string $route = null, mixed $parameters = [])
    {
        if ($route === null) {
            return back()->with($type, $message);
        }

        return redirect()->route($route, $parameters)->with($type, $message);
    }

    /**
     * Dispatch a browser event to the parent window with data.
     */
    public function dispatchBrowserEvent(string $key, mixed $data = [], int $code = 200)
    {
        if (! is_string($data)) {
            $data = Js::encode($data);
        }

        $javascript = <<<HTML
        <script>
            window.parent.postMessage({ key: '$key', data: $data }, '*');
        </script>
        HTML;

        return response()->make($javascript, $code, [
            'Content-Type' => 'text/html',
        ]);
    }
}
