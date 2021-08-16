<?php

namespace Morebec\Orkestra\Framework\Messaging;

use Morebec\Orkestra\DateTime\DateTime;

class MessageAuditEvent
{
    /**
     * Event type indicating that a message was received.
     *
     * @var string
     */
    public const MESSAGE_RECEIVED_TYPE = 'message.received';

    /**
     * Event type indicating that a message was processed and a response received.
     */
    public const MESSAGE_PROCESSED_TYPE = 'message.processed';

    /**
     * Date and time at which this event occurred.
     */
    public DateTime $occurredAt;

    /**
     * Type name of the message.
     */
    public string $messageTypeName;

    /**
     * Normalized form of the message data.
     */
    public array $message;

    /**
     * Headers of the message.
     */
    public array $messageHeaders;

    /**
     * ID of the message.
     */
    public string $messageId;

    /**
     * Causation ID of the message.
     */
    public ?string $causationId;

    /**
     * Correlation ID of the message.
     */
    public string $correlationId;

    /**
     * Type of the event.
     */
    public string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function messageReceived(): self
    {
        return new self(self::MESSAGE_RECEIVED_TYPE);
    }

    public static function messageProcessed(): self
    {
        return new self(self::MESSAGE_PROCESSED_TYPE);
    }
}
