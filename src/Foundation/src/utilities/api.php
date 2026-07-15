<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Throw an API exception.
 */
function throw_api_exception(Throwable $e): JsonResponse
{
    $code = match (true) {
        $e instanceof HttpException => $e->getStatusCode(),
        $e instanceof ModelNotFoundException => 404,
        $e instanceof ValidationException => 422,
        $e instanceof AuthenticationException => 401,
        $e instanceof AuthorizationException => 403,
        default => 500,
    };

    $default = Response::$statusTexts[$code] ?? 'Something went wrong';

    $message = (config('app.debug') || $code < 500)
        ? ($e->getMessage() ?: $default)
        : $default;

    $payload = match (true) {
        $e instanceof ValidationException => $e->validator->errors()->toArray(),
        config('app.debug') => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace()],
        default => [],
    };

    return response()->json([
        'code' => $code,
        'success' => false,
        'message' => $message,
        'payload' => $payload,
    ], $code);
}
