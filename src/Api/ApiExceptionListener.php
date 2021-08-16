<?php

namespace Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Messaging\Authorization\UnauthorizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to kernel exceptions for the API paths and returns an API response appropriately.
 * Also prevents Symfony from redirecting to the login page for unauthorized access exceptions.
 */
class ApiExceptionListener implements EventSubscriberInterface
{
    private JsonResponseFactory $jsonResponseFactory;
    private LoggerInterface $logger;
    private string $apiBasePath;

    public function __construct(
        LoggerInterface $logger,
        JsonResponseFactory $jsonResponseFactory,
        string $apiBasePath = ApiRequestListener::API_BASE_PATH
    ) {
        $this->jsonResponseFactory = $jsonResponseFactory;
        $this->logger = $logger;
        $this->apiBasePath = $apiBasePath;
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        if (!$this->supportsException($event)) {
            return;
        }

        $errorType = (new \ReflectionClass($exception))->getShortName();
        $errorMessage = $exception->getMessage();

        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof UnauthorizedException) {
            $statusCode = Response::HTTP_FORBIDDEN;
        }

        $response = $this->jsonResponseFactory->makeFailureResponse(
            $errorType,
            $errorMessage,
            null,
            $statusCode
        );

        /* @noinspection JsonEncodingApiUsageInspection */
        $this->logger->debug('ApiExceptionListener - Generated response', [
            'errorType' => $errorType,
            'errorMessage' => $errorMessage,
            'statusCode' => $statusCode,
            'data' => json_decode($response->getContent(), true),
        ]);
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    protected function supportsException(ExceptionEvent $event): bool
    {
        $request = $event->getRequest();

        return $request->getContentType() === 'json' || str_contains($request->getUri(), $this->apiBasePath);
    }
}
