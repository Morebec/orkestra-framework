<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Web\Api;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Since Symfony handles things in a particular way when receiving post
 * data, this listener fetches the information from the content of the body
 * and makes it available in the post variable for the api endpoints.
 */
class ApiRequestListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $baseApiPath;

    public function __construct(string $baseApiPath = '/api/')
    {
        $this->baseApiPath = $baseApiPath;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // only check master request
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Check for the API
        if (!str_contains($request->getUri(), $this->baseApiPath)) {
            return;
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        $contentType = $request->getContentType();
        if ($contentType !== 'json') {
            return;
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new InvalidApiRequestException(['message' => 'Invalid JSON received.']);
        }

        $request->request->replace($data);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }
}
