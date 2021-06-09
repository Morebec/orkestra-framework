<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\Messaging\Timeout\MessageBusTimeoutPublisher;
use Morebec\Orkestra\Messaging\Timeout\PollingTimeoutProcessor;
use Morebec\Orkestra\Messaging\Timeout\PollingTimeoutProcessorOptions;
use Morebec\Orkestra\Messaging\Timeout\TimeoutStorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MainTimerProcessorConsoleCommand extends Command implements SignalableCommandInterface
{
    protected static $defaultName = 'orkestra:timeout-processor';
    /**
     * @var MessageBusTimeoutPublisher
     */
    private $timeoutPublisher;

    /**
     * @var ClockInterface
     */
    private $clock;
    /**
     * @var TimeoutStorageInterface
     */
    private $timeoutStorage;

    /** @var SymfonyStyle */
    private $io;

    public function __construct(
        MessageBusTimeoutPublisher $timeoutPublisher,
        ClockInterface $clock,
        TimeoutStorageInterface $timeoutStorage
    ) {
        parent::__construct();
        $this->timeoutPublisher = $timeoutPublisher;
        $this->clock = $clock;
        $this->timeoutStorage = $timeoutStorage;
    }

    public function getSubscribedSignals(): array
    {
        return [\SIGTERM, \SIGINT];
    }

    public function handleSignal(int $signal): void
    {
        $this->io->writeln('Timeout Processor Stopping ...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Timeout Processor');

        $options = new PollingTimeoutProcessorOptions();
        $options->withName('main');
        $options->withMaximumProcessingTime(PollingTimeoutProcessorOptions::INFINITE);
        $processor = new PollingTimeoutProcessor($this->clock, $this->timeoutPublisher, $this->timeoutStorage, $options);

        $this->io->writeln('Timeout Processor Started.');
        $processor->start();

        $this->io->writeln('Timeout Processor Stopped.');

        return self::SUCCESS;
    }

    protected function onInterruption($input, $output): void
    {
    }
}
