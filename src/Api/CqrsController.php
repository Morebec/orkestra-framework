<?php

namespace Morebec\Orkestra\Framework\Api;

use InvalidArgumentException;
use Morebec\Orkestra\Messaging\MessageBusInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseStatusCode;
use Morebec\Orkestra\Messaging\MessageHeaders;
use Morebec\Orkestra\Messaging\MessageInterface;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Implementation of a controller that supports CQRS messages (Commands and Queries)
 * natively.
 * **This implementation is used by spectool for code generation of endpoints.**.
 */
class CqrsController extends AbstractController
{
    protected MessageBusInterface $messageBus;
    protected HttpObjectNormalizerInterface $objectNormalizer;
    protected JsonResponseFactory $jsonResponseFactory;
    protected MessageNormalizerInterface $messageNormalizer;
    protected ValidatorInterface $validator;

    public function __construct(
        MessageBusInterface $messageBus,
        HttpObjectNormalizerInterface $objectNormalizer,
        JsonResponseFactory $jsonResponseFactory,
        MessageNormalizerInterface $messageNormalizer,
        ValidatorInterface $validator
    ) {
        $this->messageBus = $messageBus;
        $this->objectNormalizer = $objectNormalizer;
        $this->jsonResponseFactory = $jsonResponseFactory;
        $this->messageNormalizer = $messageNormalizer;
        $this->validator = $validator;
    }

    /**
     * Validates request data against validation constraints and returns the errors.
     */
    protected function validateRequestData(array $requestData, Constraint $constraints): ConstraintViolationListInterface
    {
        return $this->validator->validate($requestData, $constraints);
    }

    /**
     * Handles validation errors and returns a response accordingly.
     */
    protected function handleValidationErrors(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            // Replace the default [] symfony surrounds the name of the field with
            $field = str_replace(['[', ']'], '', $field);
            $errors[$field] = $violation->getMessage();
        }

        return $this->makeFailureResponse(
            'InvalidApiRequestException',
            'The request was invalid',
            $errors,
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Denormalizes a {@link MessageInterface} from data as an array.
     */
    protected function denormalizeMessage(array $data, string $className): MessageInterface
    {
        return $this->messageNormalizer->denormalize($data, $className);
    }

    /**
     * Sends a message on the message bus.
     */
    protected function sendMessage(MessageInterface $message, ?MessageHeaders $headers = null): MessageBusResponseInterface
    {
        return $this->messageBus->sendMessage($message, $headers);
    }

    /**
     * Handles a successful response from the message bus by converting it to a JsonResponse.
     */
    protected function handleSuccessfulMessageBusResponse(MessageBusResponseInterface $response, int $statusCode, array $httpHeaders = []): JsonResponse
    {
        if ($response->isFailure()) {
            throw new InvalidArgumentException('The response provided was not successful.');
        }
        $data = $this->objectNormalizer->normalize($response->getPayload());

        return $this->makeSuccessResponse($data, $statusCode, $httpHeaders);
    }

    /**
     * Builds a {@link JsonResponse} from a {@link MessageBusResponseInterface}.
     *
     * @throws ReflectionException
     */
    protected function handleFailureMessageBusResponse(MessageBusResponseInterface $response, array $exceptionStatusCodeMapping = [], array $httpHeaders = []): JsonResponse
    {
        if ($response->isSuccess()) {
            throw new InvalidArgumentException('The response provided was not a failure.');
        }

        $error = $response->getPayload();
        $errorType = (new ReflectionClass($error))->getShortName();

        $statusCode = ($exceptionStatusCodeMapping + [
                    'NotFoundException' => Response::HTTP_NOT_FOUND,
                    'UnauthorizedException' => Response::HTTP_FORBIDDEN,
                ])[$errorType] ?? ([
                // If no known error, return a status code based on the Message Bus Response
                MessageBusResponseStatusCode::FAILED => Response::HTTP_INTERNAL_SERVER_ERROR,
                MessageBusResponseStatusCode::INVALID => Response::HTTP_BAD_REQUEST,
                MessageBusResponseStatusCode::REFUSED => Response::HTTP_BAD_REQUEST,
            ][(string) $response->getStatusCode()]);

        $errorMessage = $error->getMessage();
        // Hide internal errors to users.
        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $errorType = 'UnexpectedErrorException';
            if (getenv('APP_ENV') !== 'dev') {
                $errorMessage = 'An unexpected internal error occurred.';
            }
        }

        return $this->makeFailureResponse($errorType, $errorMessage, null, $statusCode, $httpHeaders);
    }

    /**
     * Makes a success JSON response.
     *
     * @param mixed $data
     */
    protected function makeSuccessResponse($data, int $statusCode, array $httpHeaders = []): JsonResponse
    {
        return $this->jsonResponseFactory->makeSuccessResponse($data, $statusCode, $httpHeaders);
    }

    /**
     * Makes a failure JSON response.
     */
    protected function makeFailureResponse(string $errorType, string $errorMessage, $data, int $statusCode, array $httpHeaders = []): JsonResponse
    {
        return $this->jsonResponseFactory->makeFailureResponse($errorType, $errorMessage, $data, $statusCode, $httpHeaders);
    }
}
