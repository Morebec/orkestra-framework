<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\ApiRequestListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiRequestListenerTest extends TestCase
{
    public function testOnKernelRequest(): void
    {
        $listener = new ApiRequestListener();

        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'json',
        ], json_encode([
            'key' => 'value',
        ], \JSON_THROW_ON_ERROR));

        $request->headers->set('Content-Type', 'application/json');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->onKernelRequest($event);

        $request = $event->getRequest();
        self::assertEquals([
            'key' => 'value',
        ], $request->request->all());
    }
}
