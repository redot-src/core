<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Redot\Http\Middleware\RoutePermission;
use Redot\Models\Setting;
use Symfony\Component\HttpKernel\Exception\HttpException;

afterEach(function () {
    File::deleteDirectory(public_path('helper-image-test'));
});

it('trims, drops empty entries, and normalises csv strings and arrays', function () {
    expect(parse_csv(' alpha, beta ,,gamma '))->toBe(['alpha', 'beta', 'gamma'])
        ->and(parse_csv([' one ', '', ' two ']))->toBe(['one', 'two']);
});

it('honours a custom separator and callback when parsing csv input', function () {
    $parsed = parse_csv('  a |B|c  ', '|', fn (string $value): string => strtoupper(trim($value)));

    expect($parsed)->toBe(['A', 'B', 'C']);
});

it('limits collections and appends a localized remaining-count notice', function () {
    expect(collect_ellipsis(['a', 'b', 'c', 'd'], 2, ':count more')->all())
        ->toBe(['a', 'b', '2 more']);
});

it('returns the input unchanged when the collection is at or under the limit', function () {
    expect(collect_ellipsis(['a', 'b'], 2)->all())->toBe(['a', 'b'])
        ->and(collect_ellipsis(['a'], 2)->all())->toBe(['a']);
});

it('creates proportionally scaled image thumbnails', function () {
    $directory = public_path('helper-image-test');
    $source = $directory . '/source.png';
    File::ensureDirectoryExists($directory);

    $image = imagecreatetruecolor(200, 100);
    imagepng($image, $source);
    unset($image);

    $thumbnail = create_thumbnail($source, 100, 100, 90);
    $thumbnailPath = public_path($thumbnail);

    expect($thumbnail)->toBe('/helper-image-test/thumbnails/source-thumb-100x100-q90.png')
        ->and(File::exists($thumbnailPath))->toBeTrue()
        ->and(getimagesize($thumbnailPath))->toMatchArray([100, 50]);
});

it('caches separate thumbnails for different rendering options', function (int $width, int $height, int $quality, array $dimensions) {
    $directory = public_path('helper-image-test');
    $source = $directory . '/source.png';
    File::ensureDirectoryExists($directory);

    $image = imagecreatetruecolor(200, 100);
    imagepng($image, $source);
    unset($image);

    $original = create_thumbnail($source, 100, 100, 90);
    $variant = create_thumbnail($source, $width, $height, $quality);

    expect($variant)->not->toBe($original)
        ->and(File::exists(public_path($original)))->toBeTrue()
        ->and(File::exists(public_path($variant)))->toBeTrue()
        ->and(getimagesize(public_path($original)))->toMatchArray([100, 50])
        ->and(getimagesize(public_path($variant)))->toMatchArray($dimensions);
})->with([
    'width' => [150, 100, 90, [150, 75]],
    'height' => [100, 25, 90, [50, 25]],
    'quality' => [100, 100, 80, [100, 50]],
]);

it('reuses a current thumbnail for identical rendering options', function () {
    $directory = public_path('helper-image-test');
    $source = $directory . '/source.png';
    File::ensureDirectoryExists($directory);

    $image = imagecreatetruecolor(200, 100);
    imagepng($image, $source);
    unset($image);

    $thumbnail = create_thumbnail($source, 100, 100, 90);

    Image::shouldReceive('fromPath')->never();

    expect(create_thumbnail($source, 100, 100, 90))->toBe($thumbnail);
});

it('regenerates a thumbnail when its source image changes', function () {
    $directory = public_path('helper-image-test');
    $source = $directory . '/source.png';
    File::ensureDirectoryExists($directory);

    $image = imagecreatetruecolor(200, 100);
    imagepng($image, $source);
    unset($image);

    $thumbnail = create_thumbnail($source, 100, 100, 90);

    $image = imagecreatetruecolor(200, 200);
    imagepng($image, $source);
    unset($image);
    touch($source, filemtime(public_path($thumbnail)) + 1);
    clearstatcache();

    expect(create_thumbnail($source, 100, 100, 90))->toBe($thumbnail)
        ->and(getimagesize(public_path($thumbnail)))->toMatchArray([100, 100]);
});

it('returns an authenticated json error payload for authentication exceptions', function () {
    config()->set('app.debug', false);

    $response = throw_api_exception(new AuthenticationException);

    expect($response->getStatusCode())->toBe(401)
        ->and($response->getData(true))->toMatchArray([
            'code' => 401,
            'success' => false,
            'message' => 'Unauthenticated.',
            'payload' => [],
        ]);
});

it('exposes validator errors when converting a validation exception to an api payload', function () {
    $exception = ValidationException::withMessages([
        'email' => ['The email field is required.'],
    ]);

    $response = throw_api_exception($exception);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['payload'])->toBe([
            'email' => ['The email field is required.'],
        ]);
});

it('hides the raw exception message for 5xx responses when debug mode is off', function () {
    config()->set('app.debug', false);

    $response = throw_api_exception(new RuntimeException('internal database failure'));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true))->toMatchArray([
            'code' => 500,
            'success' => false,
            'message' => 'Internal Server Error',
            'payload' => [],
        ]);
});

it('forwards http exception status codes to the api payload', function () {
    $response = throw_api_exception(new HttpException(418, "I'm a teapot"));

    expect($response->getStatusCode())->toBe(418)
        ->and($response->getData(true)['message'])->toBe("I'm a teapot");
});

it('returns the locale-aware application name from the setting, falling back to app.name', function () {
    Setting::set('app_name', ['en' => 'My Site', 'ar' => 'موقعي']);

    app()->setLocale('ar');
    expect(app_name())->toBe('موقعي');

    app()->setLocale('en');
    expect(app_name())->toBe('My Site');

    Setting::set('app_name', ['en' => '']);
    app()->setLocale('en');
    config()->set('app.name', 'Configured Fallback');

    expect(app_name())->toBe('Configured Fallback');
});

it('resolves a route name from a url that matches a registered route', function () {
    Route::name('posts.show')->get('/posts/{post}', fn () => 'show');

    expect(route_from_url(url('/posts/42')))->toBe('posts.show');
});

it('returns null when the url does not match any registered route', function () {
    expect(route_from_url('http://localhost/no-such-route'))->toBeNull();
});

it('only applies route authorization to urls on the application host', function () {
    Route::get('/protected', fn () => 'protected')
        ->middleware(RoutePermission::class)
        ->name('protected');
    Route::getRoutes()->refreshNameLookups();

    expect(url_allowed('https://evil.example/protected?return=http://localhost'))->toBeTrue()
        ->and(url_allowed('http://localhost/protected'))->toBeFalse()
        ->and(url_allowed('/protected'))->toBeFalse();
});

it('goes back to previous urls that belong to the application', function (string $appUrl, string $previous) {
    Route::get('/fallback', fn () => 'fallback')->name('fallback');
    Route::getRoutes()->refreshNameLookups();

    config()->set('app.url', $appUrl);
    URL::useOrigin($appUrl);

    request()->headers->set('referer', $previous);

    expect(back_or_route('fallback'))->toBe($previous);
})->with([
    'same origin' => ['https://app.com', 'https://app.com/users'],
    'mixed case with default port' => ['https://app.com', 'HTTPS://APP.com:443/users'],
    'inside the base path' => ['https://app.com/admin', 'https://app.com/admin/users'],
    'the base path itself' => ['https://app.com/admin', 'https://app.com/admin'],
    'base path with trailing slash' => ['https://app.com/admin/', 'https://app.com/admin/users'],
]);

it('falls back to the route for previous urls outside the application', function (string $appUrl, string $previous) {
    Route::get('/fallback', fn () => 'fallback')->name('fallback');
    Route::getRoutes()->refreshNameLookups();

    config()->set('app.url', $appUrl);
    URL::useOrigin($appUrl);

    request()->headers->set('referer', $previous);

    expect(back_or_route('fallback'))->toBe(route('fallback'));
})->with([
    'look-alike host' => ['https://app.com', 'https://app.com.evil.com/users'],
    'different scheme' => ['https://app.com', 'http://app.com/users'],
    'different port' => ['https://app.com', 'https://app.com:8443/users'],
    'scheme-relative url' => ['https://app.com', '//app.com/users'],
    'javascript url' => ['https://app.com', 'javascript:alert(1)'],
    'malformed url' => ['https://app.com', 'http://[bad'],
    'path sharing the base prefix' => ['https://app.com/admin', 'https://app.com/administrator'],
    'path outside the base path' => ['https://app.com/admin', 'https://app.com/other'],
]);
