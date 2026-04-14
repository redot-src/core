<?php

namespace Redot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class EnsureDependenciesBuilt
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = public_path('assets/dist/lock.json');

        if (! file_exists($lockFile)) {
            Artisan::call('dependencies:build');

            return $next($request);
        }

        if (file_exists($lockFile)) {
            $lock = json_decode(file_get_contents($lockFile), true);

            foreach ($lock['files'] as $file => $timestamp) {
                $path = base_path($file);

                if (! file_exists($path) || $timestamp !== filemtime($path)) {
                    Artisan::call('dependencies:build');
                    break;
                }
            }

            foreach ($lock['directories'] as $directory => $timestamp) {
                $path = base_path($directory);

                if (! file_exists($path) || $timestamp !== filemtime($path)) {
                    Artisan::call('dependencies:build');
                    break;
                }
            }
        }

        return $next($request);
    }
}
