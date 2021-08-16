<?php

namespace Morebec\Orkestra\Framework\Messaging;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;
use Ramsey\Uuid\Uuid;

/**
 * Implementation of a {@link MessageAuditEventStorageInterface} based on PostgreSql.
 */
class PostgreSqlMessageAuditEventStorage implements MessageAuditEventStorageInterface
{
    private PostgreSqlDocumentStore $store;
    private string $collectionName;

    public function __construct(
        Connection $connection,
        ClockInterface $clock,

        string $collectionName = 'message_audit'
    ) {
        $configuration = new PostgreSqlDocumentStoreConfiguration();
        $configuration->collectionPrefix = '';
        $this->store = new PostgreSqlDocumentStore($connection, $configuration, $clock);
        $this->collectionName = $collectionName;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function add(MessageAuditEvent $event): void
    {
        $this->store->insertDocument($this->collectionName, Uuid::uuid4(), (array) $event);
    }
}
