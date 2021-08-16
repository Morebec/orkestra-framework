<?php

namespace Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpLoggerListener implements EventSubscriberInterface
{
    public const REQUEST_ID_KEY = 'RequestId';
    private LoggerInterface $logger;
    private ClockInterface $clock;

    public function __construct(LoggerInterface $logger, ClockInterface $clock)
    {
        $this->logger = $logger;
        $this->clock = $clock;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1],
            KernelEvents::RESPONSE => ['onResponse', 256],
            KernelEvents::EXCEPTION => ['onException', 256],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $throwable = $event->getThrowable();

        /** @var DateTime $requestDate */
        $requestDate = $request->attributes->get('Date', $this->clock->now());
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        $exceptionClass = \get_class($throwable);
        $exceptionMessage = $throwable->getMessage();

        $requestId = $request->attributes->get(self::REQUEST_ID_KEY);

        $context = [
            'path' => $path,
            'method' => $method,
            'hostname' => $request->getHost(),
            'ipAddress' => $request->getClientIp(),
            'date' => $requestDate,
            'duration' => (int) (($this->clock->now()->getMillisTimestamp() * 1000) - ($requestDate->getMillisTimestamp() * 1000)),
            'requestId' => $requestId,
            'headers' => $request->headers->all(),
//            'request' => (string) $request,
//            'response' => (string) $response,
            'exceptionClass' => $exceptionClass,
            'exceptionMessage' => $exceptionMessage,
            'exceptionFile' => $throwable->getFile(),
            'exceptionLine' => $throwable->getLine(),
            'exceptionTrace' => $throwable->getTrace(),
        ];

        $this->logger->error("$requestId - Exception encountered - {$method} {$path} - $exceptionClass - $exceptionMessage", $context);
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $requestId = uniqid('req_', true);
        $requestDate = $this->clock->now();

        $request->attributes->set(self::REQUEST_ID_KEY, $requestId);
        $request->attributes->set('Date', $requestDate);

        $path = $request->getPathInfo();
        $method = $request->getMethod();

        $context = [
            'path' => $path,
            'method' => $method,
            'hostname' => $request->getHost(),
            'ipAddress' => $request->getClientIp(),
            'date' => $requestDate,
            'requestId' => $requestId,
            'headers' => $request->headers->all(),
//            'request' => (string) $request,
//            'response' => (string) $response,
        ];
        $this->logger->info("$requestId - Request received - {$method} {$path}", $context);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        /** @var DateTime $requestDate */
        $requestDate = $request->attributes->get('Date', $this->clock->now());
        $method = $request->getMethod();
        $statusCode = $response->getStatusCode();
        $path = $request->getPathInfo();

        $requestId = $request->attributes->get(self::REQUEST_ID_KEY);

        $context = [
            'path' => $path,
            'method' => $method,
            'statusCode' => $statusCode,
            'hostname' => $request->getHost(),
            'ipAddress' => $request->getClientIp(),
            'date' => $requestDate,
            'duration' => (int) (($this->clock->now()->getMillisTimestamp() * 1000) - ($requestDate->getMillisTimestamp() * 1000)),
            'requestId' => $requestId,
            'headers' => $request->headers->all(),
//            'request' => (string) $request,
//            'response' => (string) $response,
        ];

        if (!$response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
            $context['content'] = $response->getContent();
        }

        $this->logger->log(
            $response->isSuccessful() ? LogLevel::INFO : LogLevel::ERROR,
            "$requestId - Response returned - {$statusCode} {$method} {$path}",
            $context
        );
    }
}
