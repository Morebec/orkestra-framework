<?php

namespace Morebec\Orkestra\Framework\Messaging;

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
        $this->logger->info(
            "[MessageAudit][corrId:{$event->correlationId}][causId:{$event->causationId}][msgId:{$event->messageId}]: {$event->type}",
            (array) $event
        );
    }
}
