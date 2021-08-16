<?php

namespace Morebec\Orkestra\Framework\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API can return two types of responses:
 * - Successes
 * - Failures.
 *
 * Both types of responses follow a similar schema:
 * - status: indicates if it is a success or failure response
 * - data: represents the payload of the response.
 *
 * The only difference is that the failure response has two additional fields:
 * - error: Represents the type of error that caused this response to be about a failure. E.g. (unauthorized_access)
 * - message: The message explaining the error. (E.g.: You are not authorized to access this resource).
 */
class JsonResponseFactory
{
    public function makeSuccessResponse($data, int $statusCode = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ], $statusCode, $headers);
    }

    public function makeFailureResponse(string $errorType, string $errorMessage, $data, int $statusCode, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'failure',
            'error' => $errorType,
            'message' => $errorMessage,
            'data' => $data,
        ], $statusCode, $headers);
    }
}
