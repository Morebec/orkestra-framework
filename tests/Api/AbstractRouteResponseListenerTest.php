<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\AbstractRouteResponseListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AbstractRouteResponseListenerTest extends TestCase
{
    public function testOnResponse(): void
    {
        $listener = new class() extends AbstractRouteResponseListener {
            protected function supportsRoute(Request $request, string $routeName): bool
            {
                return $routeName === 'unit_test';
            }

            protected function handleResponse(string $routeName, Request $request, Response $response): void
            {
                $response->setContent('changed');
            }
        };

        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();

        $request = new Request([], [], ['_route' => 'unit_test'], [], [], [], 'not_changed');
        $response = new Response('not_changed');

        $event = new ResponseEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response,
        );

        $listener->onKernelResponse($event);

        self::assertEquals('changed', $response->getContent());
    }
}
