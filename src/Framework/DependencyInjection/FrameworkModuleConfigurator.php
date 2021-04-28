<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\DateTime\SystemClock;
use Morebec\Orkestra\EventSourcing\EventProcessor\EventStorePositionStorageInterface;
use Morebec\Orkestra\EventSourcing\EventProcessor\MessageBusEventPublisher;
use Morebec\Orkestra\EventSourcing\EventStore\EventStoreInterface;
use Morebec\Orkestra\EventSourcing\EventStore\MessageBusContextEventStoreDecorator;
use Morebec\Orkestra\EventSourcing\EventStore\UpcastingEventStoreDecorator;
use Morebec\Orkestra\Messaging\Timer\MessageBusTimerPublisher;
use Morebec\Orkestra\Messaging\Timer\TimerManager;
use Morebec\Orkestra\Messaging\Timer\TimerManagerInterface;
use Morebec\Orkestra\Messaging\Timer\TimerStorageInterface;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainEventProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainProjectionEventProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\MainTimerProcessorConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\OrkestraFrameworkQuickstartConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand\StartRoadRunnerConsoleCommand;
use Morebec\Orkestra\OrkestraFramework\Framework\Projection\PostgreSqlProjectorGroup;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\Api\ApiExceptionListener;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\Api\ApiRequestListener;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\Api\CommandController;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\Api\QueryController;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\Api\WebSocketController;
use Morebec\Orkestra\OrkestraFramework\Framework\Web\DefaultController;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStore;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorage;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorageConfiguration;
use Morebec\Orkestra\PostgreSqlTimerStorage\PostgreSqlTimerStorage;
use Morebec\Orkestra\SymfonyBundle\Module\SymfonyOrkestraModuleConfiguratorInterface;
use Morebec\Orkestra\SymfonyBundle\Module\SymfonyOrkestraModuleContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class FrameworkModuleConfigurator implements SymfonyOrkestraModuleConfiguratorInterface
{
    public function configureContainer(ContainerConfigurator $container): void
    {
        $config = new SymfonyOrkestraModuleContainerConfigurator($container);
        $config->services()
                ->defaults()
                    ->autoconfigure()
                    ->autowire()
        ;

        $config->service(ClockInterface::class, SystemClock::class);

        $config->service(Connection::class)->factory([ConnectionFactory::class, 'create']);

        // Event Store
        $this->setupEventStore($config);

        // Event Processing
        $this->setupEventProcessor($config);

        // Timers and Timer Processing
        $this->setupTimerProcessing($config);

        // General Storage
        $this->setupDocumentStore($config);

        // Projection
        $this->setupProjectors($config);

        // API Listeners
        $this->setupApiServices($config);

        // Road Runner Commands
        $config->consoleCommand(StartRoadRunnerConsoleCommand::class);

        if ($_ENV['APP_ENV'] === 'dev') {
            $config->consoleCommand(OrkestraFrameworkQuickstartConsoleCommand::class);
            $config->controller(DefaultController::class);
        }
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../Web/DefaultController.php', 'annotation');

        // API
        $this->setupApiRoutes($routes);
    }

    protected function setupApiServices(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(ApiExceptionListener::class);
        $config->service(ApiRequestListener::class);
        $config->service(CommandController::class);
        $config->service(QueryController::class);
        $config->service(WebSocketController::class);
    }

    protected function setupApiRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../Web/Api', 'annotation')
            ->namePrefix('api.v1.')
            ->prefix('/api/v1');
    }

    private function setupEventStore(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(PostgreSqlEventStoreConfiguration::class)
            ->factory([PostgreSqlEventStoreConfigurationFactory::class, 'create']);
        $config->service(PostgreSqlEventStore::class);

        $config->service(UpcastingEventStoreDecorator::class)
            ->decorate(EventStoreInterface::class, null, 1)
            ->args([service('.inner')]);

        $config->service(EventStoreInterface::class, PostgreSqlEventStore::class);
        $config->service(MessageBusContextEventStoreDecorator::class)
            ->decorate(EventStoreInterface::class, null, 0)
            ->args([service('.inner')]);

        $config->service(PostgreSqlEventStorePositionStorageConfiguration::class)
            ->factory([PostgreSqlEventStorePositionStorageConfigurationFactory::class, 'create']);
        $config->service(EventStorePositionStorageInterface::class, PostgreSqlEventStorePositionStorage::class);
    }

    private function setupEventProcessor(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(MessageBusEventPublisher::class);
        $config->consoleCommand(MainEventProcessorConsoleCommand::class);
    }

    private function setupTimerProcessing(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(TimerManagerInterface::class, TimerManager::class);
        $config->service(PostgreSqlTimerStorageFactory::class);
        $config->service(TimerStorageInterface::class, PostgreSqlTimerStorage::class)
            ->factory([service(PostgreSqlTimerStorageFactory::class), 'create']);
        $config->service(MessageBusTimerPublisher::class);
        $config->consoleCommand(MainTimerProcessorConsoleCommand::class);
    }

    private function setupDocumentStore(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(PostgreSqlDocumentStoreConfiguration::class)->factory(
            [PostgreSqlDocumentStoreConfigurationFactory::class, 'create']
        );
        $config->service(PostgreSqlDocumentStore::class);
    }

    private function setupProjectors(SymfonyOrkestraModuleContainerConfigurator $config): void
    {
        $config->service(PostgreSqlProjectorGroup::class);
        $config->consoleCommand(MainProjectionEventProcessorConsoleCommand::class)->arg(0, service(PostgreSqlProjectorGroup::class));
    }
}
