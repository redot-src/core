<?php

namespace Redot\Auth\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

trait IssuesApiTokens
{
    /**
     * Issue a bearer token for the given user and build the API response.
     */
    protected function issueToken(Authenticatable $user, int $code = 200): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respond(
            code: $code,
            payload: [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        );
    }
}
