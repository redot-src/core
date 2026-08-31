<?php

namespace Redot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $key = $scope . '_locale';
        $locales = setting($scope . '_locales');

        $originalRouteLocale = $request->route()->parameter('locale');

        $locale = Arr::first(
            [
                $request->query('locale'),
                $originalRouteLocale,
                session($key),
                $request->cookie($key),
                $request->getPreferredLanguage($locales),
            ],
            fn ($locale) => is_string($locale) && in_array($locale, $locales),
            Arr::first($locales),
        );

        session()->put($key, $locale);
        app()->setLocale($locale);

        URL::defaults(['locale' => $locale]);
        $request->route()->forgetParameter('locale');

        // Redirect to the correct locale if the current locale is not the original route locale
        if ($originalRouteLocale !== null && $locale !== $originalRouteLocale) {
            $localizedPath = "/$locale" . $request->getPathInfo();
            $url = str($request->getPathInfo())->replaceFirst($originalRouteLocale, $locale);

            try {
                $localizedRoute = Route::getRoutes()->match(Request::create($localizedPath, $request->method()));

                if (! $localizedRoute->isFallback) $url = $localizedPath;
            } catch (HttpExceptionInterface) {
                // The path does not exist with the locale prepended.
            }

            $qs = $request->getQueryString();

            if ($qs) {
                $url .= "?$qs";
            }

            $response = redirect()->to($url, 301);
        } else {
            $response = $next($request);
        }

        $response->headers->setCookie(cookie()->forever($key, $locale));

        return $response;
    }
}
