<?php

namespace Morebec\Orkestra\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\EventSourcing\EventProcessor\MessageBusEventPublisher;
use Morebec\Orkestra\EventSourcing\EventStore\MessageBusContextEventStoreDecorator;
use Morebec\Orkestra\EventSourcing\EventStore\UpcastingEventStoreDecorator;
use Morebec\Orkestra\Framework\Api\ApiExceptionListener;
use Morebec\Orkestra\Framework\Api\ApiRequestListener;
use Morebec\Orkestra\Framework\Api\HttpLoggerListener;
use Morebec\Orkestra\Framework\ConsoleCommand\StartRoadRunnerConsoleCommand;
use Morebec\Orkestra\Framework\EventStore\GitHashEventStoreDecorator;
use Morebec\Orkestra\Framework\EventStore\GitWrapper;
use Morebec\Orkestra\Framework\Messaging\MessageAuditEventStorageInterface;
use Morebec\Orkestra\Framework\Messaging\MessageAuditMiddleware;
use Morebec\Orkestra\Framework\Messaging\PostgreSqlMessageAuditEventStorage;
use Morebec\Orkestra\Messaging\Context\BuildMessageBusContextMiddleware;
use Morebec\Orkestra\Messaging\Context\MessageBusContextManager;
use Morebec\Orkestra\Messaging\Context\MessageBusContextManagerInterface;
use Morebec\Orkestra\Messaging\Context\MessageBusContextProvider;
use Morebec\Orkestra\Messaging\Context\MessageBusContextProviderInterface;
use Morebec\Orkestra\Messaging\MessageBusInterface;
use Morebec\Orkestra\Messaging\Middleware\LoggerMiddleware;
use Morebec\Orkestra\Messaging\Routing\LoggingMessageHandlerInterceptor;
use Morebec\Orkestra\Messaging\Timeout\MessageBusTimeoutPublisher;
use Morebec\Orkestra\Messaging\Timeout\TimeoutStorageInterface;
use Morebec\Orkestra\Normalization\ObjectNormalizer;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStore;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStoreConfiguration;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorage;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorageConfiguration;
use Morebec\Orkestra\PostgreSqlTimeoutStorage\PostgreSqlTimeoutStorage;
use Morebec\Orkestra\SymfonyBundle\Command\DebugMessageClassMapConsoleCommand;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\EventProcessing\EventProcessingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\EventProcessing\ProjectionProcessingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\EventStore\EventStoreConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\Messaging\MessageBusConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\Messaging\MessagingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\Messaging\TimeoutProcessingConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\NotConfiguredException;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\OrkestraConfiguration;
use Morebec\Orkestra\SymfonyBundle\DependencyInjection\Configuration\OrkestraModuleConfiguratorInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Is a module configurator that can serve as a base of example to wire the orkestra services.
 */
class FrameworkModuleConfigurator implements OrkestraModuleConfiguratorInterface
{
    public function configureContainer(OrkestraConfiguration $configuration): void
    {
        $configuration
            ->usingSystemClock();

        // DATABASE CONNECTION
        $this->configureConnection($configuration);

        // MESSAGING
        $this->configureMessaging($configuration);

        // TIMEOUT PROCESSING
        $this->configureTimeoutProcessing($configuration);

        // EVENT STORE
        $this->configureEventStore($configuration);

        // EVENT PROCESSING
        $this->configureEventProcessing($configuration);

        // HTTP API
        $this->configureHttp($configuration);

        // Debugging console commands
        $this->configureDebugConsoleCommands($configuration);

        // General Storage
        $configuration->service(PostgreSqlDocumentStoreConfiguration::class)->factory(
            [PostgreSqlDocumentStoreConfigurationFactory::class, 'create']
        );
        $configuration->service(PostgreSqlDocumentStore::class);

        // Object Normalization
        $configuration->service(ObjectNormalizerInterface::class, ObjectNormalizer::class);
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
    }

    /**
     * Configures the message bus.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureMessaging(OrkestraConfiguration $configuration): void
    {
        $configuration->configureMessaging((new MessagingConfiguration()));

        $configuration->service(MessageBusContextProviderInterface::class, MessageBusContextProvider::class);
        $configuration->service(MessageBusContextManagerInterface::class, MessageBusContextManager::class);

        try {
            $messageBus = $configuration->messaging()->messageBus(MessageBusInterface::class);
        } catch (NotConfiguredException $exception) {
            $messageBus = new MessageBusConfiguration();
            $messageBus->usingServiceId(MessageBusInterface::class);
            $configuration
                ->messaging()
                ->configureMessageBus($messageBus);
        }

        $messageBus
                ->withMiddleware(BuildMessageBusContextMiddleware::class)
                ->withMiddleware(LoggerMiddleware::class)
                ->withMiddlewareAfter(MessageAuditMiddleware::class, LoggerMiddleware::class);

        $configuration->service(MessageAuditEventStorageInterface::class, PostgreSqlMessageAuditEventStorage::class);
        $configuration->service(LoggingMessageHandlerInterceptor::class)
            ->tag('monolog.logger', ['channel' => 'message_bus'])
        ;
    }

    /**
     * Configures the event store.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureEventStore(OrkestraConfiguration $configuration): void
    {
        $configuration->service(PostgreSqlEventStoreConfiguration::class)
            ->factory([PostgreSqlEventStoreConfigurationFactory::class, 'create']);

        $configuration->configureEventStore(
            (new EventStoreConfiguration())
                ->usingImplementation(PostgreSqlEventStore::class)
                ->decoratedBy(GitHashEventStoreDecorator::class)
                ->decoratedBy(UpcastingEventStoreDecorator::class)
                ->decoratedBy(MessageBusContextEventStoreDecorator::class)
        );

        $configuration->service(GitWrapper::class);
    }

    /**
     * Configures event processing services, such as position storage, event publisher.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureEventProcessing(OrkestraConfiguration $configuration): void
    {
        $configuration
            ->configureEventProcessing(
                (new EventProcessingConfiguration())
                    ->usingEventStorePositionStorageImplementation(PostgreSqlEventStorePositionStorage::class)
                    ->configureProjectionProcessing(
                        (new ProjectionProcessingConfiguration())
                    )
            );

        $configuration->service(PostgreSqlEventStorePositionStorageConfiguration::class)
            ->factory([PostgreSqlEventStorePositionStorageConfigurationFactory::class, 'create']);

        // Event Queue
        $configuration->service(MessageBusEventPublisher::class);
    }

    /**
     * Configures the HTTP related services.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureHttp(OrkestraConfiguration $configuration): void
    {
        $configuration->service(ApiRequestListener::class);
        $configuration->service(ApiExceptionListener::class);

        // Road Runner Commands
        $configuration->consoleCommand(StartRoadRunnerConsoleCommand::class)
            ->arg(0, '%kernel.project_dir%/');
    }

    /**
     * Configures the database connection.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureConnection(OrkestraConfiguration $configuration): void
    {
        $configuration->service(ConnectionFactory::class);
        $configuration->service(Connection::class)
            ->factory([service(ConnectionFactory::class), 'create']);
    }

    /**
     * Configures the service requirements for timeout processing.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureTimeoutProcessing(OrkestraConfiguration $configuration): void
    {
        $configuration->messaging()->configureTimeoutProcessing(
            (new TimeoutProcessingConfiguration())
                ->usingDefaultManagerImplementation()
                ->usingStorageImplementation(PostgreSqlTimeoutStorage::class)
        );

        $configuration->service(PostgreSqlTimeoutStorageFactory::class);
        $configuration->service(TimeoutStorageInterface::class, PostgreSqlTimeoutStorage::class)
            ->factory([service(PostgreSqlTimeoutStorageFactory::class), 'create']);

        $configuration->service(MessageBusTimeoutPublisher::class);
    }

    /**
     * Configures some debugging console commands.
     * @param OrkestraConfiguration $configuration
     * @return void
     */
    protected function configureDebugConsoleCommands(OrkestraConfiguration $configuration): void
    {
        $configuration->consoleCommand(DebugMessageClassMapConsoleCommand::class);
        // $configuration->consoleCommand(DebugMessageRouterConsoleCommand::class);
    }
}
