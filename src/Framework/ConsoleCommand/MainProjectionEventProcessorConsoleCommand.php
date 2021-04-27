<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Morebec\Orkestra\EventSourcing\EventProcessor\EventStorePositionStorageInterface;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\Projection\ProjectorEventPublisher;
use Morebec\Orkestra\EventSourcing\Projection\ProjectorGroup;
use Morebec\Orkestra\EventSourcing\Projection\ProjectorInterface;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventProcessor;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStore;
use Morebec\Orkestra\SymfonyBundle\Command\AbstractEventProcessorConsoleCommand;

class MainProjectionEventProcessorConsoleCommand extends AbstractEventProcessorConsoleCommand
{
    protected static $defaultName = 'orkestra:projection-processor';
    /**
     * @var ProjectorInterface
     */
    private $projector;

    public function __construct(
        ProjectorInterface $projector,
        EventStoreInterface $eventStore,
        PostgreSqlEventStore $postgreSqlEventStore,
        EventStorePositionStorageInterface $eventStorePositionStorage
    ) {
        $processor = new PostgreSqlEventProcessor(
            new ProjectorEventPublisher($projector),
            $eventStore,
            $postgreSqlEventStore,
            $eventStorePositionStorage
        );

        $this->projector = $projector;

        $name = $projector instanceof ProjectorGroup ? $projector->getName() : 'PostgreSQL';

        parent::__construct($processor, null, "{$name} Projection Processor");
    }

    protected function resetProcessor(): void
    {
        $this->projector->reset();
        parent::resetProcessor();
    }
}
