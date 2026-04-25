<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Redot\Http\Middleware\EnsureDependenciesBuilt;
use Symfony\Component\HttpFoundation\Response;

it('continues without rebuilding when the dependency lock is current', function () {
    $directory = public_path('assets/dist');
    $trackedFile = 'composer.json';

    File::ensureDirectoryExists($directory);
    File::put($directory . '/lock.json', json_encode([
        'files' => [
            $trackedFile => filemtime(base_path($trackedFile)),
        ],
        'directories' => [],
    ]));

    $response = (new EnsureDependenciesBuilt)->handle(
        Request::create('/'),
        fn () => new Response('next')
    );

    expect($response->getContent())->toBe('next');

    File::delete($directory . '/lock.json');
});
