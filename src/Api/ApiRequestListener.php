<?php

namespace Morebec\Orkestra\Framework\Api;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Since Symfony handles things in a particular way when receiving post
 * data, this listener fetches the information from the content of the body
 * and makes it available as an array instead of a string.
 */
class ApiRequestListener implements EventSubscriberInterface
{
    public const API_BASE_PATH = '/api/';

    private string $baseApiPath;

    public function __construct(string $baseApiPath = self::API_BASE_PATH)
    {
        $this->baseApiPath = $baseApiPath;
    }

    /**
     * @throws InvalidApiRequestException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // only check master request
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Check for the API
        if (!$this->supportsRequest($request)) {
            return;
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        $contentType = $request->getContentType();
        if ($contentType !== 'json') {
            return;
        }

        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            if (!$data) {
                throw new JsonException('Invalid JSON received');
            }
        } catch (JsonException $e) {
            throw new InvalidApiRequestException('Invalid JSON received.');
        }

        $request->request->replace($data);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    protected function supportsRequest(Request $request): bool
    {
        return str_contains($request->getUri(), $this->baseApiPath);
    }
}
