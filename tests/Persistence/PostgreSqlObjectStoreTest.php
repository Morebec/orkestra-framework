<?php

namespace Tests\Morebec\Orkestra\Framework\Persistence;

use Morebec\Orkestra\Framework\Persistence\PostgreSqlObjectStore;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use PHPUnit\Framework\TestCase;

class PostgreSqlObjectStoreTest extends TestCase
{
    public function testRemoveObject(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
                ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testRemoveObject';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('removeDocument')
        ;
        $store->removeObject('id');
    }

    public function testFindAll(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
                ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testFindAll';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore->expects($this->once())->method('findAllDocuments');
        $store->findAll();
    }

    public function testFindById(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testFindById';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('findOneDocument')
        ;

        $store->findById('id');
    }

    public function testFindManyBy(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testFindManyBy';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('findManyDocuments')
        ;

        $store->findManyBy('id');
    }

    public function testAddObject(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
                ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testAddObject';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();
        $normalizer->method('normalize')->willReturn([]);

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('insertDocument')
        ;

        $store->addObject('id', new \stdClass());
    }

    public function testClear(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testClear';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('dropCollection')
        ;

        $store->clear();
    }

    public function testFindOneBy(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collectionName = 'testFindOneBy';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);
        $documentStore
            ->expects($this->once())
            ->method('findOneDocument')
        ;

        $store->findOneBy('id');
    }

    public function testUpdateObject(): void
    {
        $documentStore = $this->getMockBuilder(PostgreSqlDocumentStore::class)
            ->disableOriginalConstructor()
            ->getMock()
            ;
        $collectionName = 'testUpdateObject';
        $className = self::class;
        $normalizer = $this->getMockBuilder(ObjectNormalizerInterface::class)->getMock();
        $normalizer->method('normalize')->willReturn([]);

        $store = new PostgreSqlObjectStore($documentStore, $collectionName, $className, $normalizer);

        $documentStore
            ->expects($this->once())
            ->method('updateDocument')
        ;

        $store->updateObject('id', new \stdClass());
    }
}
