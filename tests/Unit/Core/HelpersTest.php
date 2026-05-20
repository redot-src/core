<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Redot\Models\Setting;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

it('returns an authenticated json error payload for authentication exceptions', function () {
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
