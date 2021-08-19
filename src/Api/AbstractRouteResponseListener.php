<?php

namespace Morebec\Orkestra\Framework\Api;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Implementation of a Response Listener that should run only on specific route names of master requests.
 */
abstract class AbstractRouteResponseListener implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $routeName = $request->attributes->get('_route');
        if (!$this->supportsRoute($request, $routeName)) {
            return;
        }

        $this->handleResponse($routeName, $request, $response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Indicates if this Listener supports a given route name.
     * Returns true if supported, otherwise false.
     */
    abstract protected function supportsRoute(Request $request, string $routeName): bool;

    /**
     * Perform work on the supported route.
     */
    abstract protected function handleResponse(string $routeName, Request $request, Response $response): void;
}
