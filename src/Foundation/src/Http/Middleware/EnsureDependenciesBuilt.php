<?php

namespace Redot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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
        $lockFile = dist_path('lock.json');

        if ($this->dependenciesNeedBuilding($lockFile)) {
            Cache::lock('redot.dependencies.build', 60)->block(60, function () use ($lockFile) {
                if ($this->dependenciesNeedBuilding($lockFile)) {
                    Artisan::call('dependencies:build');
                }
            });
        }

        return $next($request);
    }

    /**
     * Determine whether the built dependencies are missing or stale.
     */
    protected function dependenciesNeedBuilding(string $lockFile): bool
    {
        $contents = @file_get_contents($lockFile);

        if ($contents === false) {
            return true;
        }

        $lock = json_decode($contents, true);

        if (! is_array($lock) || ! isset($lock['files'], $lock['directories'])) {
            return true;
        }

        foreach (['files', 'directories'] as $type) {
            foreach ($lock[$type] as $path => $timestamp) {
                $path = base_path($path);

                if (! file_exists($path) || $timestamp !== filemtime($path)) {
                    return true;
                }
            }
        }

        return false;
    }
}
