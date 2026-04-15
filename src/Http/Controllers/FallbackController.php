<?php

namespace Redot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if (! in_array($request->method(), ['GET', 'HEAD'])) {
            abort(404);
        }

        if (! config('redot.routing.append_locale_to_url') || ! config('redot.routing.redirect_non_locale_urls')) {
            abort(404);
        }

        $locale = app()->getLocale();
        $route = Route::getRoutes()->match(Request::create("/$locale" . $request->getPathInfo(), 'GET'));

        if ($route->isFallback) {
            abort(404);
        }

        // Append query string if it exists
        if (null !== $qs = $request->getQueryString()) {
            $qs = '?' . $qs;
        }

        return redirect()->to("/$locale" . $request->getPathInfo() . $qs, 301);
    }
}
