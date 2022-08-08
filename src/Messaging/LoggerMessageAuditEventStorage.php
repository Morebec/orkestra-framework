<?php

namespace Morebec\Orkestra\Framework\Messaging;

use Morebec\Orkestra\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class LoggerMessageAuditEventStorage implements MessageAuditEventStorageInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function add(MessageAuditEvent $event): void
    {
        $context = (array) $event;
        $context['occurredAt'] = $event->occurredAt->format(DateTime::RFC3339_EXTENDED);

        if ($event->type === MessageAuditEvent::MESSAGE_PROCESSED_TYPE && $context['messageHeaders']['response']['failed']) {
            $this->logger->error(
                'Message Audit: {type} {messageTypeName}',
                $context
            );
        } else {
            $this->logger->info(
                'Message Audit: {type} {messageTypeName}',
                $context
            );
        }
    }
}
