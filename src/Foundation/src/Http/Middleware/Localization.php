<?php

namespace Redot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Determine the scope of the request (website or dashboard)
        $scope = $request->routeIs('dashboard.*') || $request->is('dashboard') ? 'dashboard' : 'website';

        $key = $scope . '_locale';
        $locales = setting($scope . '_locales');

        $fallback = Arr::first($locales);
        $originalRouteLocale = $request->route()->parameter('locale');
        $locale = $request->query('locale') ?: $originalRouteLocale ?: session($key) ?: $request->getPreferredLanguage($locales);

        if (! $locale || ! in_array($locale, $locales)) {
            $locale = $fallback;
        }

        session()->put($key, $locale);
        app()->setLocale($locale);

        URL::defaults(['locale' => $locale]);
        $request->route()->forgetParameter('locale');

        // Redirect to the correct locale if the current locale is not the original route locale
        if ($originalRouteLocale !== null && $locale !== $originalRouteLocale) {
            $url = str($request->getPathInfo())->replaceFirst($originalRouteLocale, $locale);
            $qs = $request->getQueryString();

            if ($qs) {
                $url .= "?$qs";
            }

            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}
