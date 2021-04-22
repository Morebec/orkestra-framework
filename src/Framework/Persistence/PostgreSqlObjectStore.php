<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Persistence;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\Filter\Filter;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;

/**
 * ObjectStore based on {@link PostgreSqlDocumentStore}, that allows to easily persist and load
 * Objects without any ORM.
 */
class PostgreSqlObjectStore
{
    /**
     * @var PostgreSqlDocumentStore
     */
    private $store;
    /**
     * @var string
     */
    private $collectionName;
    /**
     * @var string
     */
    private $objectClassName;
    /**
     * @var ObjectNormalizerInterface
     */
    private $normalizer;

    public function __construct(
        PostgreSqlDocumentStore $store,
        string $collectionName,
        string $className,
        ObjectNormalizerInterface $normalizer
    ) {
        $this->store = $store;
        $this->collectionName = $collectionName;
        $this->objectClassName = $className;
        $this->normalizer = $normalizer;
    }

    public function addObject(string $id, $o): void
    {
        $this->store->insertDocument($this->collectionName, $id, $this->normalizer->normalize($o));
    }

    public function updateObject(string $id, $o): void
    {
        $this->store->updateDocument($this->collectionName, $id, $this->normalizer->normalize($o));
    }

    public function removeObject(string $id): void
    {
        $this->store->removeDocument($this->collectionName, $id);
    }

    public function findById(string $id)
    {
        return $this->findOneBy(Filter::findById($id));
    }

    public function findOneBy(string $filter)
    {
        $doc = $this->store->findOneDocument($this->collectionName, $filter);
        if (!$doc) {
            return null;
        }

        return $this->denormalizeObject($doc);
    }

    public function findManyBy(string $filter): array
    {
        $docs = $this->store->findManyDocuments($this->collectionName, $filter);

        return array_map([$this, 'denormalizeObject'], $docs);
    }

    public function clear()
    {
        $this->store->dropCollection($this->collectionName);
    }

    public function getConnection(): Connection
    {
        return $this->store->getConnection();
    }

    protected function normalizeObject($object): array
    {
        return $this->normalizer->normalize($object);
    }

    protected function denormalizeObject($object)
    {
        return $this->normalizer->denormalize($object, $this->objectClassName);
    }
}
