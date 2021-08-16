<?php

namespace Tests\Morebec\Orkestra\Framework\Messaging;

use Morebec\Orkestra\DateTime\DateTime;
use Morebec\Orkestra\DateTime\FixedClock;
use Morebec\Orkestra\Framework\Messaging\MessageAuditEvent;
use Morebec\Orkestra\Framework\Messaging\MessageAuditEventStorageInterface;
use Morebec\Orkestra\Framework\Messaging\MessageAuditMiddleware;
use Morebec\Orkestra\Messaging\MessageBusResponseStatusCode;
use Morebec\Orkestra\Messaging\MessageHandlerResponse;
use Morebec\Orkestra\Messaging\MessageHeaders;
use Morebec\Orkestra\Messaging\MessageInterface;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Throwable;

class MessageAuditMiddlewareTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testInvoke(): void
    {
        $messageNormalizer = $this->getMockBuilder(MessageNormalizerInterface::class)->getMock();
        $messageNormalizer->method('normalize')->willReturn([
            'key' => 'value',
        ]);
        $systemDate = new DateTime('2021-01-01 15:00:00');
        $clock = new FixedClock($systemDate);
        $auditEventStorage = $this->getMockBuilder(MessageAuditEventStorageInterface::class)->getMock();

        $middleware = new MessageAuditMiddleware(
            $messageNormalizer,
            $clock,
            $auditEventStorage,
            new NullLogger()
        );

        $message = new class() implements MessageInterface {
            public static function getTypeName(): string
            {
                return 'message';
            }
        };

        $auditEventStorage
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$this->callback(function (MessageAuditEvent $event) use ($systemDate) {
                    self::assertEquals($systemDate, $event->occurredAt);
                    self::assertEquals('message', $event->messageTypeName);
                    self::assertEquals([
                        'key' => 'value',
                    ], $event->message);
                    self::assertEquals([
                        MessageHeaders::MESSAGE_ID => 'msg_611ab3de731b79',
                        MessageHeaders::CORRELATION_ID => 'cor_611ab3de731bc9',
                    ], $event->messageHeaders);
                    self::assertEquals('msg_611ab3de731b79', $event->messageId);
                    self::assertNull($event->causationId);
                    self::assertEquals('cor_611ab3de731bc9', $event->correlationId);
                    self::assertEquals(MessageAuditEvent::MESSAGE_RECEIVED_TYPE, $event->type);

                    return true;
                })],

                [$this->callback(function (MessageAuditEvent $event) use ($systemDate) {
                    self::assertEquals($systemDate, $event->occurredAt);
                    self::assertEquals('message', $event->messageTypeName);
                    self::assertEquals([
                        'key' => 'value',
                    ], $event->message);
                    self::assertEquals([
                        MessageHeaders::MESSAGE_ID => 'msg_611ab3de731b79',
                        MessageHeaders::CORRELATION_ID => 'cor_611ab3de731bc9',
                        MessageAuditMiddleware::HEADER_PROCESSING_STARTED_AT => $systemDate->getMillisTimestamp(),
                        MessageAuditMiddleware::HEADER_PROCESSING_ENDED_AT => $systemDate->getMillisTimestamp(),
                        MessageAuditMiddleware::HEADER_RESPONSE => [
                            'failed' => false,
                            'statusCode' => MessageBusResponseStatusCode::SUCCEEDED,
                            'handlerName' => 'unit_test',
                            'exception' => null,
                        ],
                    ], $event->messageHeaders);
                    self::assertEquals('msg_611ab3de731b79', $event->messageId);
                    self::assertNull($event->causationId);
                    self::assertEquals('cor_611ab3de731bc9', $event->correlationId);
                    self::assertEquals(MessageAuditEvent::MESSAGE_PROCESSED_TYPE, $event->type);

                    return true;
                })]
            );

        $middleware(
            $message,
            new MessageHeaders([
                MessageHeaders::MESSAGE_ID => 'msg_611ab3de731b79',
                MessageHeaders::CORRELATION_ID => 'cor_611ab3de731bc9',
            ]),
            fn (MessageInterface $message, MessageHeaders $headers) => new MessageHandlerResponse('unit_test', MessageBusResponseStatusCode::SUCCEEDED())
        );
    }
}
