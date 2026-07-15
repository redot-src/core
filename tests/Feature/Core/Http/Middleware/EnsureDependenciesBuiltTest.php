<?php

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Redot\Http\Middleware\EnsureDependenciesBuilt;

it('rebuilds dependencies when the lock file is malformed', function (string $contents) {
    File::put(dist_path('lock.json'), $contents);

    $artisan = Mockery::mock();
    $artisan->shouldReceive('call')
        ->once()
        ->with('dependencies:build')
        ->andReturnUsing(function () {
            File::replace(dist_path('lock.json'), json_encode([
                'files' => [],
                'directories' => [],
            ]));
        });
    Artisan::swap($artisan);

    $response = (new EnsureDependenciesBuilt)->handle(
        Request::create('/'),
        fn () => new Response('next')
    );

    expect($response->getContent())->toBe('next');
})->with([
    'partial JSON' => '{"files":',
    'JSON null' => 'null',
    'missing directories' => '{"files":[]}',
    'non-array files' => '{"files":null,"directories":[]}',
]);

it('checks the lock file again after acquiring the build lock', function () {
    File::put(dist_path('lock.json'), '{');

    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')
        ->once()
        ->with(60, Mockery::type(Closure::class))
        ->andReturnUsing(function (int $seconds, Closure $callback) {
            File::replace(dist_path('lock.json'), json_encode([
                'files' => [],
                'directories' => [],
            ]));

            return $callback();
        });

    Cache::shouldReceive('lock')
        ->once()
        ->with('redot.dependencies.build', 60)
        ->andReturn($lock);
    $artisan = Mockery::mock();
    $artisan->shouldReceive('call')->never();
    Artisan::swap($artisan);

    (new EnsureDependenciesBuilt)->handle(
        Request::create('/'),
        fn () => new Response('next')
    );
});

it('does not acquire the build lock when dependencies are current', function () {
    File::put(dist_path('lock.json'), json_encode([
        'files' => [],
        'directories' => [],
    ]));

    Cache::shouldReceive('lock')->never();
    $artisan = Mockery::mock();
    $artisan->shouldReceive('call')->never();
    Artisan::swap($artisan);

    (new EnsureDependenciesBuilt)->handle(
        Request::create('/'),
        fn () => new Response('next')
    );
});

it('rebuilds dependencies when a tracked source file is modified', function () {
    $source = base_path('lang/en.json');
    File::ensureDirectoryExists(dirname($source));
    File::put($source, '{}');

    $relative = str_replace(base_path(), '', $source);

    File::put(dist_path('lock.json'), json_encode([
        'files' => [
            $relative => filemtime($source) - 10,
        ],
        'directories' => [],
    ]));

    $artisan = Mockery::mock();
    $artisan->shouldReceive('call')
        ->once()
        ->with('dependencies:build')
        ->andReturnUsing(function () use ($relative, $source) {
            File::replace(dist_path('lock.json'), json_encode([
                'files' => [
                    $relative => filemtime($source),
                ],
                'directories' => [],
            ]));
        });
    Artisan::swap($artisan);

    $response = (new EnsureDependenciesBuilt)->handle(
        Request::create('/'),
        fn () => new Response('next')
    );

    expect($response->getContent())->toBe('next');
});

it('clears the build output when a dependencies build is triggered', function () {
    File::ensureDirectoryExists(dist_path('translations'));
    File::put(dist_path('lock.json'), '{}');
    File::put(dist_path('init.js'), 'window.__inits = {};');

    trigger_dependencies_build();

    expect(File::exists(dist_path('lock.json')))->toBeFalse()
        ->and(File::exists(dist_path('init.js')))->toBeFalse();
});
