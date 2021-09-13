<?php

namespace Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\DateTime\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HttpLoggerListener implements EventSubscriberInterface
{
    public const REQUEST_ID_ATTRIBUTE = 'requestId';
    private const REQUEST_STARTED_AT_ATTRIBUTE = 'requestStartedAt';
    private const REQUEST_DURATION_ATTRIBUTE = 'requestDuration';

    private LoggerInterface $logger;
    private ClockInterface $clock;

    public function __construct(ClockInterface $clock, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->clock = $clock;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1000],
            KernelEvents::RESPONSE => ['onResponse', -1000],
            KernelEvents::TERMINATE => ['onTerminate', -1000],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $request->attributes->set(self::REQUEST_ID_ATTRIBUTE, $request->attributes->get(self::REQUEST_ID_ATTRIBUTE, Uuid::uuid4()->toString()));

        $request->attributes->set(self::REQUEST_STARTED_AT_ATTRIBUTE, $this->clock->now()->getMillisTimestamp() * 1000);

        $this->logger->info('Request received: {requestMethod} {requestPath} {requestScheme}', [
            'requestId' => $request->attributes->get(self::REQUEST_ID_ATTRIBUTE),
            'requestMethod' => $request->getMethod(),
            'requestPath' => $request->getPathInfo(),
            'requestScheme' => $request->getScheme(),
            'requestUri' => $request->getUri(),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $requestStartedAt = $request->attributes->get(self::REQUEST_STARTED_AT_ATTRIBUTE);
        $requestDuration = $this->clock->now()->getMillisTimestamp() - ($requestStartedAt / 1000);

        $request->attributes->set(self::REQUEST_DURATION_ATTRIBUTE, $requestDuration);

        $this->logger->info('Response returned: {requestMethod} {requestPath} {requestScheme} {responseStatusCode} {requestDuration}', [
            'requestId' => $request->attributes->get(self::REQUEST_ID_ATTRIBUTE),
            'requestMethod' => $request->getMethod(),
            'requestPath' => $request->getPathInfo(),
            'requestScheme' => $request->getScheme(),
            'requestUri' => $request->getUri(),
            'responseStatusCode' => $response->getStatusCode(),
            'requestDuration' => $requestDuration,
        ]);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info('{requestMethod} {requestPath} {requestScheme} {responseStatusCode}', [
            'requestId' => $request->attributes->get(self::REQUEST_ID_ATTRIBUTE),
            'requestMethod' => $request->getMethod(),
            'requestPath' => $request->getPathInfo(),
            'requestScheme' => $request->getScheme(),
            'requestUri' => $request->getUri(),
            'responseStatusCode' => $response->getStatusCode(),
            'requestDuration' => $request->attributes->get(self::REQUEST_DURATION_ATTRIBUTE),
        ]);
    }
}
