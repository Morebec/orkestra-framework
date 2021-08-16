<?php

namespace Morebec\Orkestra\Framework\Messaging;

interface MessageAuditEventStorageInterface
{
    /**
     * Adds an event to this storage.
     */
    public function add(MessageAuditEvent $event): void;
}
