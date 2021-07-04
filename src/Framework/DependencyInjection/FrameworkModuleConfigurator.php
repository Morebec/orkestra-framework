<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\EventSourcing\EventProcessor\EventStorePositionStorageInterface;
use Morebec\Orkestra\EventSourcing\EventProcessor\MessageBusEventPublisher;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\MessageBusContextEventStoreDecorator;
use Morebec\Orkestra\EventSourcing\EventStore\UpcastingEventStoreDecorator;
use Morebec\Orkestra\Messaging\Timeout\InMemoryTimeoutStorage;
use Morebec\Orkestra\Messaging\Timeout\MessageBusTimeoutPublisher;
use Morebec\Orkestra\Messaging\Timeout\TimeoutManager;
use Morebec\Orkestra\Messaging\Timeout\TimeoutManagerInterface;
use Morebec\Orkestra\Messaging\Timeout\TimeoutStorageInterface;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainEventProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainProjectionEventProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainTimeoutProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\OrkestraFrameworkQuickstartConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\StartRoadRunnerConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\Projection\PostgreSqlProjectorGroup;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\DefaultController;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStore;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorage;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorageConfiguration;
use Morebec\Orkestra\PostgreSqlTimeoutStorage\PostgreSqlTimeoutStorage;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\DefaultMessageBusConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\EventProcessingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\EventStoreConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\OrkestraConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\OrkestraModuleConfiguratorInterface;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\ProjectionProcessingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\ProjectorGroupConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\TimeoutProcessingConfiguration;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class FrameworkModuleConfigurator implements OrkestraModuleConfiguratorInterface
{
    public function configureContainer(OrkestraConfiguration $config): void
    {
        $config->usingSystemClock();

        $config->service(Connection::class)->factory([ConnectionFactory::class, 'create']);

        // Message Bus
        $config->configureMessageBus((new DefaultMessageBusConfiguration()));

        // Timeout processing
        $config->configureTimeoutProcessing(
            (new TimeoutProcessingConfiguration())
                ->usingDefaultManagerImplementation()
                ->usingStorageImplementation(InMemoryTimeoutStorage::class)
        );

        // Event Store
        $config->configureEventStore(
            (new EventStoreConfiguration())
                ->usingInMemoryImplementation()
                ->decoratedBy(MessageBusContextEventStoreDecorator::class)
                ->decoratedBy(UpcastingEventStoreDecorator::class)
        );

        // Event Processing & Projections
        $config->configureEventProcessing(
            (new EventProcessingConfiguration())
                ->usingInMemoryEventStorePositionStorage()

                // Projection Processing
                ->configureProjectionProcessing((new ProjectionProcessingConfiguration())
                    ->configureProjectionGroup(
                        (new ProjectorGroupConfiguration('primary'))
                    )
                    ->configureProjectionGroup(
                        (new ProjectorGroupConfiguration('secondary'))
                    )
                )
        );

        $config->consoleCommand(ProjectionGroupEventProcessorConsoleCommand::class);

        // Road Runner Commands
        $config->consoleCommand(StartRoadRunnerConsoleCommand::class);

//        // Event Store
//        $this->setupEventStore();
//
//        // Event Processing
//        $this->setupEventProcessor();
//
//        // Timeouts and Timeout Processing
//        $this->setupTimeoutProcessing();
//
//        // General Storage
//        $this->setupDocumentStore();
//
//        // Projection
//        $this->setupProjectors();
//
//        // Road Runner Commands
//        $config->consoleCommand(StartRoadRunnerConsoleCommand::class);
//
        if ($_ENV['APP_ENV'] === 'dev') {
            $config->consoleCommand(OrkestraFrameworkQuickstartConsoleCommand::class);
            $config->controller(DefaultController::class);
        }
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../Web/DefaultController.php', 'annotation');
    }

    private function setupEventStore(OrkestraConfiguration $config): void
    {
        $config->service(PostgreSqlEventStoreConfiguration::class)
            ->factory([PostgreSqlEventStoreConfigurationFactory::class, 'create']);
        $config->service(PostgreSqlEventStore::class)
            ->alias(EventStoreInterface::class, PostgreSqlEventStore::class)
        ;

        $config->service(UpcastingEventStoreDecorator::class)
            ->decorate(EventStoreInterface::class, null, 1)
            ->args([service('.inner')]);

        $config->service(MessageBusContextEventStoreDecorator::class)
            ->decorate(EventStoreInterface::class, null, 0)
            ->args([service('.inner')]);

        $config->service(PostgreSqlEventStorePositionStorageConfiguration::class)
            ->factory([PostgreSqlEventStorePositionStorageConfigurationFactory::class, 'create']);
        $config->service(EventStorePositionStorageInterface::class, PostgreSqlEventStorePositionStorage::class);
    }

    private function setupEventProcessor(OrkestraConfiguration $config): void
    {
        $config->service(MessageBusEventPublisher::class);
        $config->consoleCommand(MainEventProcessorConsoleCommand::class);
    }

    private function setupTimeoutProcessing(OrkestraConfiguration $config): void
    {
        $config->service(TimeoutManagerInterface::class, TimeoutManager::class);
        $config->service(PostgreSqlTimeoutStorageFactory::class);
        $config->service(TimeoutStorageInterface::class, PostgreSqlTimeoutStorage::class)
            ->factory([service(PostgreSqlTimeoutStorageFactory::class), 'create']);
        $config->service(MessageBusTimeoutPublisher::class);
        $config->consoleCommand(MainTimeoutProcessorConsoleCommand::class);
    }

    private function setupDocumentStore(OrkestraConfiguration $config): void
    {
        $config->service(PostgreSqlDocumentStoreConfiguration::class)->factory(
            [PostgreSqlDocumentStoreConfigurationFactory::class, 'create']
        );
        $config->service(PostgreSqlDocumentStore::class);
    }

    private function setupProjectors(OrkestraConfiguration $config): void
    {
        $config->service(PostgreSqlProjectorGroup::class);
        $config->consoleCommand(MainProjectionEventProcessorConsoleCommand::class)->arg(0, service(PostgreSqlProjectorGroup::class));
    }
}
