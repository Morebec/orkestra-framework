<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\JsonResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseFactoryTest extends TestCase
{
    public function testMakeFailureResponse(): void
    {
        $factory = new JsonResponseFactory();
        $response = $factory->makeFailureResponse(
            'UnexpectedError',
            'An unexpected error occurred',
            null,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [
                'X-Unit-Test' => true,
            ]
        );

        self::assertEquals('{"status":"failure","error":"UnexpectedError","message":"An unexpected error occurred","data":null}', $response->getContent());
        self::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Unit-Test'));
        self::assertTrue($response->headers->get('X-Unit-Test'));
    }

    public function testMakeSuccessResponse(): void
    {
        $factory = new JsonResponseFactory();
        $response = $factory->makeSuccessResponse(['key' => 'value'], Response::HTTP_CREATED, ['X-Unit-Test' => true]);

        self::assertEquals('{"status":"success","data":{"key":"value"}}', $response->getContent());
        self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Unit-Test'));
        self::assertTrue($response->headers->get('X-Unit-Test'));
    }
}
