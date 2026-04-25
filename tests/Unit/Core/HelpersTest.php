<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

it('formats csv strings and arrays into trimmed values', function () {
    expect(parse_csv(' alpha, beta ,,gamma '))->toBe(['alpha', 'beta', 'gamma'])
        ->and(parse_csv([' one ', '', ' two ']))->toBe(['one', 'two']);
});

it('limits collections and appends an ellipsis item with remaining count', function () {
    expect(collect_ellipsis(['a', 'b', 'c', 'd'], 2, ':count more')->all())
        ->toBe(['a', 'b', '2 more']);
});

it('returns consistent api exception payloads', function () {
    $response = throw_api_exception(new AuthenticationException);

    expect($response->getStatusCode())->toBe(401)
        ->and($response->getData(true))->toMatchArray([
            'code' => 401,
            'success' => false,
            'message' => 'Unauthenticated.',
            'payload' => [],
        ]);
});

it('includes validation errors in api exception payloads', function () {
    $exception = ValidationException::withMessages([
        'email' => ['The email field is required.'],
    ]);

    $response = throw_api_exception($exception);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['payload'])->toBe([
            'email' => ['The email field is required.'],
        ]);
});
