<?php

namespace Morebec\Orkestra\Framework\Messaging;

use const JSON_THROW_ON_ERROR;
use JsonException;
use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\Messaging\MessageBusResponseInterface;
use Morebec\Orkestra\Messaging\MessageHandlerResponse;
use Morebec\Orkestra\Messaging\MessageHeaders;
use Morebec\Orkestra\Messaging\MessageInterface;
use Morebec\Orkestra\Messaging\Middleware\MessageBusMiddlewareInterface;
use Morebec\Orkestra\Messaging\MultiMessageHandlerResponse;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

/**
 * Implementation of a messaging middleware that allows saving audit events about messages.
 */
class MessageAuditMiddleware implements MessageBusMiddlewareInterface
{
    /**
     * Message header indicating that the message failed to be used in the audit event.
     */
    public const HEADER_PROCESSING_STARTED_AT = 'processingStartedAt';

    /**
     * Message header indicating that the message failed to be used in the audit event.
     */
    public const HEADER_PROCESSING_ENDED_AT = 'processingEndedAt';

    public const HEADER_RESPONSE = 'response';

    public const HEADER_EXCEPTION = 'exception';

    private MessageNormalizerInterface $messageNormalizer;
    private ClockInterface $clock;
    private MessageAuditEventStorageInterface $storage;
    private LoggerInterface $logger;

    public function __construct(
        MessageNormalizerInterface $messageNormalizer,
        ClockInterface $clock,
        MessageAuditEventStorageInterface $auditEventStorage,
        LoggerInterface $logger
    ) {
        $this->messageNormalizer = $messageNormalizer;
        $this->clock = $clock;
        $this->storage = $auditEventStorage;
        $this->logger = $logger;
    }

    /**
     * @throws Throwable
     */
    public function __invoke(MessageInterface $message, MessageHeaders $headers, callable $next): MessageBusResponseInterface
    {
        $this->recordEvent(MessageAuditEvent::MESSAGE_RECEIVED_TYPE, $message, $headers);

        // Additional headers
        $exception = null;
        try {
            $headers->set(self::HEADER_PROCESSING_STARTED_AT, $this->clock->now()->getMillisTimestamp());

            /** @var MessageBusResponseInterface $response */
            $response = $next($message, $headers);

            $headers->set(self::HEADER_RESPONSE, $this->buildResponseData($message, $headers, $response));
        } catch (Throwable $exception) {
            $headers->set(self::HEADER_EXCEPTION, $this->buildThrowableData($exception));
        }
        $headers->set(self::HEADER_PROCESSING_ENDED_AT, $this->clock->now()->getMillisTimestamp());

        $this->recordEvent(MessageAuditEvent::MESSAGE_PROCESSED_TYPE, $message, $headers);

        if ($exception) {
            throw $exception;
        }

        /* @noinspection PhpUndefinedVariableInspection */
        return $response;
    }

    protected function recordEvent(string $type, MessageInterface $message, MessageHeaders $headers): void
    {
        $audit = new MessageAuditEvent($type);
        $audit->occurredAt = $this->clock->now();
        $audit->messageTypeName = $headers->get(MessageHeaders::MESSAGE_TYPE_NAME, $message::getTypeName());
        $audit->message = $this->normalizeMessage($message);
        $audit->messageHeaders = $headers->toArray();
        $audit->messageId = $headers->get(MessageHeaders::MESSAGE_ID);
        $audit->causationId = $headers->get(MessageHeaders::CAUSATION_ID);
        $audit->correlationId = $headers->get(MessageHeaders::CORRELATION_ID);
        $audit->type = $type;
        try {
            $this->storage->add($audit);
        } catch (\Exception $exception) {
            $this->logger->critical("[MessageAuditMiddleware] There was an exception saving the audit event: {$exception->getMessage()}");
        }
    }

    /**
     * Builds a data structure for the response received by the message bus.
     */
    private function buildResponseData(MessageInterface $message, MessageHeaders $headers, MessageBusResponseInterface $response): array
    {
        if ($response instanceof MultiMessageHandlerResponse) {
            $data = [
                'failed' => $response->isFailure(),
                'statusCode' => (string) $response->getStatusCode(),
            ];
            $data['nestedResponses'] = [];
            foreach ($response->getHandlerResponses() as $handlerResponse) {
                $data['nestedResponses'][] = $this->buildHandlerResponseData($message, $headers, $handlerResponse);
            }

            return $data;
        }

        if ($response instanceof MessageHandlerResponse) {
            return $this->buildHandlerResponseData($message, $headers, $response);
        }

        return [
            'failed' => $response->isFailure(),
            'statusCode' => (string) $response->getStatusCode(),
            'exception' => $response->isFailure() ? $this->buildThrowableData($response->getPayload()) : null,
        ];
    }

    /**
     * Builds a serializable data structure for a message handler response data.
     */
    private function buildHandlerResponseData(MessageInterface $message, MessageHeaders $headers, MessageHandlerResponse $response): array
    {
        return [
            'failed' => $response->isFailure(),
            'statusCode' => (string) $response->getStatusCode(),
            'handlerName' => $response->getHandlerName(),
            'exception' => $response->isFailure() ? $this->buildThrowableData($response->getPayload()) : null,
        ];
    }

    /**
     * Builds a serializable data structure for a throwable.
     */
    private function buildThrowableData(Throwable $exception): array
    {
        return [
            'shortName' => (new ReflectionClass($exception))->getShortName(),
            'fqn' => \get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    private function normalizeMessage(MessageInterface $message): ?array
    {
        if (method_exists($message, 'toArray')) {
            return $message->toArray();
        }

        if (method_exists($message, '__toString')) {
            try {
                // Also allows json encoded messages to be returned as arrays.
                $messageAsString = (string) $message;

                return (array) json_decode($messageAsString, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return [$messageAsString];
            }
        }

        return $this->messageNormalizer->normalize($message);
    }
}
