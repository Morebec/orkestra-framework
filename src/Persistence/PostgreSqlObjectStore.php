<?php

namespace Morebec\Orkestra\Framework\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\Filter\Filter;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;

/**
 * ObjectStore based on {@link PostgreSqlDocumentStore}, that allows to easily persist and load
 * Objects without any ORM.
 */
class PostgreSqlObjectStore
{
    private PostgreSqlDocumentStore $store;

    private string $collectionName;

    private string $objectClassName;

    private ObjectNormalizerInterface $normalizer;

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

        $this->store->createCollectionIfNotExists($collectionName);
    }

    /**
     * Adds an object to this store.
     *
     * @throws Exception
     */
    public function addObject(string $id, object $o): void
    {
        $this->store->insertDocument($this->collectionName, $id, $this->normalizer->normalize($o));
    }

    /**
     * Updates an object in this store.
     *
     * @throws Exception
     */
    public function updateObject(string $id, object $o): void
    {
        $this->store->updateDocument($this->collectionName, $id, $this->normalizer->normalize($o));
    }

    /**
     * Removes an object from this store.
     */
    public function removeObject(string $id): void
    {
        $this->store->removeDocument($this->collectionName, $id);
    }

    /**
     * Finds an Object by its ID or returns null if not found.
     *
     * @return null
     *
     * @throws Exception
     */
    public function findById(string $id): ?object
    {
        return $this->findOneBy(Filter::findById($id));
    }

    /**
     * Finds an Object by a given filter or returns null if not found.
     *
     * @param string|Filter $filter
     *
     * @throws Exception
     */
    public function findOneBy($filter): ?object
    {
        $doc = $this->store->findOneDocument($this->collectionName, $filter);
        if (!$doc) {
            return null;
        }

        return $this->denormalizeObject($doc);
    }

    /**
     * Finds many objects by a given filter.
     *
     * @param string|Filter $filter
     *
     * @throws Exception
     */
    public function findManyBy($filter): array
    {
        $docs = $this->store->findManyDocuments($this->collectionName, $filter);

        return array_map([$this, 'denormalizeObject'], $docs);
    }

    /**
     * Finds all Objects in this store.
     *
     * @throws Exception
     */
    public function findAll(): array
    {
        $docs = $this->store->findAllDocuments($this->collectionName);

        return array_map([$this, 'denormalizeObject'], $docs);
    }

    /**
     * Empties the collection containing the objects.
     *
     * @throws Exception
     */
    public function clear(): void
    {
        $this->store->dropCollection($this->collectionName);
        $this->store->createCollectionIfNotExists($this->collectionName);
    }

    /**
     * Returns the Doctrine DBAL connection.
     */
    public function getConnection(): Connection
    {
        return $this->store->getConnection();
    }

    /**
     * Normalizes an object before adding or updating it in the store.
     */
    protected function normalizeObject(object $object): array
    {
        return $this->normalizer->normalize($object);
    }

    /**
     * Denormalizes an object upon reads.
     *
     * @param mixed $objectData
     */
    protected function denormalizeObject($objectData): ?object
    {
        return $this->normalizer->denormalize($objectData, $this->objectClassName);
    }
}
