<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to kernel exceptions for the api paths and returns an api response appropriately.
 * Also prevents Symfony from redirecting to the login page for unauthorized access exceptions.
 */
class ApiExceptionListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $baseApiPath;

    public function __construct(string $baseApiPath = '/api/')
    {
        $this->baseApiPath = $baseApiPath;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_contains($request->getUri(), $this->baseApiPath)) {
            return;
        }

        $event->setResponse(
            JsonApiResponseBuilder::createFailure(
                (new \ReflectionClass($exception))->getShortName(),
                $exception->getMessage()
            )
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 256],
        ];
    }
}
