<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Morebec\Orkestra\EventSourcing\EventProcessor\EventStorePositionStorageInterface;
use Morebec\Orkestra\EventSourcing\EventProcessor\MessageBusEventPublisher;
use Morebec\Orkestra\EventSourcing\EventProcessor\SubscribedTrackingEventProcessorOptions;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventProcessor;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStore;
use Morebec\Orkestra\SymfonyBundle\Command\AbstractEventProcessorConsoleCommand;

class MainEventProcessorConsoleCommand extends AbstractEventProcessorConsoleCommand
{
    protected static $defaultName = 'orkestra:event-processor';

    public function __construct(
        MessageBusEventPublisher $eventPublisher,
        EventStoreInterface $eventStore,
        PostgreSqlEventStore $postgreSqlEventStore,
        EventStorePositionStorageInterface $eventStorePositionStorage
    ) {
        $options = (new SubscribedTrackingEventProcessorOptions())
            ->withStreamId($eventStore->getGlobalStreamId())
            ->storePositionPerBatch()
            ->storePositionAfterProcessing()
            ->withBatchSize(1000)
            ->withName('main_event_processor')
        ;

        $eventProcessor = new PostgreSqlEventProcessor($eventPublisher, $eventStore, $postgreSqlEventStore, $eventStorePositionStorage, $options);
        parent::__construct($eventProcessor, null, 'Main Event Processor');
    }

    protected function startProcessor(): void
    {
        $this->displayProgress();
        parent::startProcessor();
    }

    protected function resetProcessor(): void
    {
        $this->io->warning('Resetting the Main Event Processor might have unexpected side effects.');
        parent::resetProcessor();
    }

    protected function replayProcessor(): void
    {
        $this->io->warning('Replaying the Main Event Processor might have unexpected side effects.');
        parent::replayProcessor();
    }
}
