<?php


namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;


use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The API can return two types of responses:
 * - Successes
 * - Failures
 *
 * Both types of responses follow a similar schema:
 * - status: indicates if it is a success or failure response
 * - data: represents the payload of the response.
 *
 * The only difference is that the failure response has two additional fields:
 * - error: Represents the type of error that caused this response to be about a failure. E.g. (unauthorized_access)
 * - message: The message explaining the error. (E.g.: You are not authorized to access this resource).
 */
class JsonApiResponseBuilder
{
    private function __construct() {}

    /**
     * Creates a success response.
     * @param string $payload
     * @param int $httpStatusCode
     * @return JsonResponse
     */
    public static function createSuccess(string $payload, int $httpStatusCode = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'status' => 'succeeded',
            'data' => $payload
        ], $httpStatusCode);
    }

    /**
     * Creates a response representing a failure/error.
     * @param string $errorType
     * @param string $errorMessage
     * @param null $payload
     * @param int $httpStatusCode
     * @return JsonResponse
     */
    public static function createFailure(string $errorType, string $errorMessage, $payload = null, int $httpStatusCode = JsonResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'status' => 'failed',
            'error' => $errorType,
            'message' => $errorMessage,
            'data' => $payload,
        ], $httpStatusCode);
    }
}