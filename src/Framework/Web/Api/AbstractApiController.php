<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;

use Morebec\Orkestra\Exceptions\NotFoundExceptionInterface;
use Morebec\Orkestra\Messaging\Authorization\UnauthorizedException;
use Morebec\Orkestra\Messaging\MessageBusInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseStatusCode;
use Morebec\Orkestra\Messaging\MessageInterface;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AbstractApiController
{
    /**
     * @var MessageBusInterface
     */
    protected $messageBus;

    /**
     * @var MessageNormalizerInterface
     */
    protected $messageNormalizer;
    /**
     * @var ObjectNormalizerInterface
     */
    private $objectNormalizer;

    public function __construct(
        MessageBusInterface $messageBus,
        MessageNormalizerInterface $messageNormalizer,
        ObjectNormalizerInterface $objectNormalizer
    ) {
        $this->messageBus = $messageBus;
        $this->messageNormalizer = $messageNormalizer;
        $this->objectNormalizer = $objectNormalizer;
    }

    protected function createResponse(?MessageInterface $message, MessageBusResponseInterface $messageBusResponse): JsonResponse
    {
        if (!$messageBusResponse->isSuccess()) {
            return $this->createFailureResponse($message, $messageBusResponse);
        }

        return $this->createSuccessResponse($message, $messageBusResponse);
    }

    protected function createSuccessResponse(?MessageInterface $message, MessageBusResponseInterface $response): JsonResponse
    {
        $payload = $response->getPayload();

        if ($response->getStatusCode()->isEqualTo(MessageBusResponseStatusCode::ACCEPTED())) {
            $httpStatusCode = JsonResponse::HTTP_ACCEPTED;
        }/* elseif (!$payload) {
            $httpStatusCode = JsonResponse::HTTP_NO_CONTENT;
        }*/ else {
            $httpStatusCode = JsonResponse::HTTP_OK;
        }

        return JsonApiResponseBuilder::createSuccess($this->objectNormalizer->normalize($payload), $httpStatusCode);
    }

    protected function createFailureResponse(?MessageInterface $message, MessageBusResponseInterface $messageBusResponse): JsonResponse
    {
        $payload = $messageBusResponse->getPayload();

        if ($messageBusResponse->getStatusCode()->isEqualTo(MessageBusResponseStatusCode::INVALID())) {
            $httpStatusCode = JsonResponse::HTTP_BAD_REQUEST;
        } elseif ($messageBusResponse->getStatusCode()->isEqualTo(MessageBusResponseStatusCode::FAILED())) {
            $httpStatusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        } elseif ($messageBusResponse->getStatusCode()->isEqualTo(MessageBusResponseStatusCode::REFUSED())) {
            $httpStatusCode = $payload instanceof UnauthorizedException ? JsonResponse::HTTP_FORBIDDEN : JsonResponse::HTTP_BAD_REQUEST;
        } else {
            $httpStatusCode = JsonResponse::HTTP_BAD_REQUEST;
        }

        if ($payload instanceof \Throwable) {
            $apiErrorMessage = $payload->getMessage();
            $apiErrorType = (new \ReflectionClass($payload))->getShortName();
            if ($payload instanceof NotFoundExceptionInterface) {
                $httpStatusCode = JsonResponse::HTTP_NOT_FOUND;
            }
        } else {
            $apiErrorMessage = 'There was an error processing your request.';
            $apiErrorType = 'unprocessed_request';
        }

        return JsonApiResponseBuilder::createFailure($apiErrorType, $apiErrorMessage, null, $httpStatusCode);
    }
}
