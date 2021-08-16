<?php

namespace Tests\Morebec\Orkestra\Framework\Api;

use Morebec\Orkestra\Framework\Api\CqrsController;
use Morebec\Orkestra\Framework\Api\HttpObjectNormalizer;
use Morebec\Orkestra\Framework\Api\JsonResponseFactory;
use Morebec\Orkestra\Messaging\MessageBusInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseStatusCode;
use Morebec\Orkestra\Messaging\MessageHandlerResponse;
use Morebec\Orkestra\Messaging\MessageHeaders;
use Morebec\Orkestra\Messaging\MessageInterface;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ValidatorBuilder;

class CqrsControllerTest extends TestCase
{
    public function testControllerMessageBusSucceeded(): void
    {
        $messageBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $objectNormalizer = new HttpObjectNormalizer();
        $responseFactory = new JsonResponseFactory();
        $messageNormalizer = $this->getMockBuilder(MessageNormalizerInterface::class)->getMock();
        $validator = (new ValidatorBuilder())->getValidator();

        $controller = new class($messageBus, $objectNormalizer, $responseFactory, $messageNormalizer, $validator) extends CqrsController {
            public function __invoke(Request $request): JsonResponse
            {
                $data = $request->request->all();
                $this->validateRequestData($data, new Constraints\Collection([
                    'key' => new Constraints\NotBlank(),
                ]));

                $message = new class() implements MessageInterface {
                    public static function getTypeName(): string
                    {
                        return 'message';
                    }
                };

                $response = $this->sendMessage($message, new MessageHeaders());

                if ($response->isSuccess()) {
                    return $this->handleSuccessfulMessageBusResponse($response, Response::HTTP_OK);
                }

                return $this->handleFailureMessageBusResponse($response);
            }
        };

        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'json',
        ]);
        $request->headers->set('Content-Type', 'application/json');
        $request->request->replace(['key' => 'value']);

        // Test success response
        $messageBus->method('sendMessage')->willReturn(
            new MessageHandlerResponse('test_handler', MessageBusResponseStatusCode::SUCCEEDED(), [
                'hello' => 'world',
            ])
        );

        /** @var JsonResponse $response */
        $response = $controller($request);

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('{"status":"success","data":{"hello":"world"}}', $response->getContent());
    }

    public function testControllerMessageBusFailed(): void
    {
        $messageBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $objectNormalizer = new HttpObjectNormalizer();
        $responseFactory = new JsonResponseFactory();
        $messageNormalizer = $this->getMockBuilder(MessageNormalizerInterface::class)->getMock();
        $validator = (new ValidatorBuilder())->getValidator();

        $controller = new class($messageBus, $objectNormalizer, $responseFactory, $messageNormalizer, $validator) extends CqrsController {
            public function __invoke(Request $request): JsonResponse
            {
                $data = $request->request->all();
                $this->validateRequestData($data, new Constraints\Collection([
                    'key' => new Constraints\NotBlank(),
                ]));

                $message = new class() implements MessageInterface {
                    public static function getTypeName(): string
                    {
                        return 'message';
                    }
                };

                $response = $this->sendMessage($message, new MessageHeaders());

                if ($response->isSuccess()) {
                    return $this->handleSuccessfulMessageBusResponse($response, Response::HTTP_OK);
                }

                return $this->handleFailureMessageBusResponse($response);
            }
        };

        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'json',
        ]);
        $request->headers->set('Content-Type', 'application/json');
        $request->request->replace(['key' => 'value']);

        // Test failure response
        $messageBus->method('sendMessage')->willReturn(
            new MessageHandlerResponse('test_handler', MessageBusResponseStatusCode::FAILED(), new \RuntimeException('Test failure.'))
        );

        /** @var JsonResponse $response */
        $response = $controller($request);

        self::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertEquals('{"status":"failure","error":"UnexpectedErrorException","message":"An unexpected internal error occurred.","data":null}', $response->getContent());
    }

    public function testControllerValidationError(): void
    {
        $messageBus = $this->getMockBuilder(MessageBusInterface::class)->getMock();
        $objectNormalizer = new HttpObjectNormalizer();
        $responseFactory = new JsonResponseFactory();
        $messageNormalizer = $this->getMockBuilder(MessageNormalizerInterface::class)->getMock();
        $validator = (new ValidatorBuilder())->getValidator();

        $controller = new class($messageBus, $objectNormalizer, $responseFactory, $messageNormalizer, $validator) extends CqrsController {
            public function __invoke(Request $request): JsonResponse
            {
                $data = $request->request->all();
                $errors = $this->validateRequestData($data, new Constraints\Collection([
                    'key' => new Constraints\NotBlank(),
                ]));

                if ($errors->count()) {
                    return $this->handleValidationErrors($errors);
                }

                $message = new class() implements MessageInterface {
                    public static function getTypeName(): string
                    {
                        return 'message';
                    }
                };

                $response = $this->sendMessage($message, new MessageHeaders());

                if ($response->isSuccess()) {
                    return $this->handleSuccessfulMessageBusResponse($response, Response::HTTP_OK);
                }

                return $this->handleFailureMessageBusResponse($response);
            }
        };

        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/api/',
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'json',
        ]);
        $request->headers->set('Content-Type', 'application/json');
        $request->request->replace(['wrong_key' => 'value']);

        // Test failure response
        $messageBus->method('sendMessage')->willReturn(
            new MessageHandlerResponse('test_handler', MessageBusResponseStatusCode::FAILED(), new \RuntimeException('Test failure.'))
        );

        /** @var JsonResponse $response */
        $response = $controller($request);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertEquals(<<<'JSON'
            {"status":"failure","error":"InvalidApiRequestException","message":"The request was invalid","data":{"key":"This field is missing.","wrong_key":"This field was not expected."}}
            JSON, $response->getContent());
    }
}
