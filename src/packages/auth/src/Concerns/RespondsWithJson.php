<?php

namespace Redot\Auth\Concerns;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function respond(mixed $payload = [], string $message = 'OK', int $code = 200): JsonResponse
    {
        $parameters = [
            'code' => $code,
            'success' => true,
            'message' => $message,
        ];

        if ($payload !== null) {
            $parameters['payload'] = $payload;
        }

        return response()->json($parameters, $code);
    }

    protected function fail(string $message = 'Bad Request', int $code = 400, mixed $payload = []): JsonResponse
    {
        $parameters = [
            'code' => $code,
            'success' => false,
            'message' => $message,
        ];

        if ($payload !== null) {
            $parameters['payload'] = $payload;
        }

        throw new HttpResponseException(response()->json($parameters, $code));
    }
}
