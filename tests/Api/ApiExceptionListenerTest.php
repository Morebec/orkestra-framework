<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\ApiExceptionListener;
use Morebec\Orkestra\Framework\Api\InvalidApiRequestException;
use Morebec\Orkestra\Framework\Api\JsonResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiExceptionListenerTest extends TestCase
{
    public function testOnException()
    {
        $logger = new NullLogger();
        $jsonResponseFactory = new JsonResponseFactory();
        $listener = new ApiExceptionListener($logger, $jsonResponseFactory);

        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/',
        ], json_encode([
            'key' => 'value',
        ], \JSON_THROW_ON_ERROR));

        // Generic Exception
        $throwable = new \RuntimeException('Test Exception');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $throwable);
        $listener->onException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertEquals([
            'status' => 'failure',
            'error' => 'RuntimeException',
            'message' => 'Test Exception',
            'data' => null,
        ], json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR));

        // HttpException
        $throwable = new InvalidApiRequestException();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $throwable);
        $listener->onException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertEquals([
            'status' => 'failure',
            'error' => 'InvalidApiRequestException',
            'message' => 'Invalid API Request.',
            'data' => null,
        ], json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR));
    }
}
