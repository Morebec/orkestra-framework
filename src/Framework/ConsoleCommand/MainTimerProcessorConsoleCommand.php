<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\Messaging\Timer\MessageBusTimerPublisher;
use Morebec\Orkestra\Messaging\Timer\PollingTimerProcessor;
use Morebec\Orkestra\Messaging\Timer\PollingTimerProcessorOptions;
use Morebec\Orkestra\Messaging\Timer\TimerStorageInterface;
use Morebec\Orkestra\SymfonyBundle\Command\AbstractInterruptibleConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MainTimerProcessorConsoleCommand extends AbstractInterruptibleConsoleCommand
{
    protected static $defaultName = 'orkestra:timer-processor';
    /**
     * @var MessageBusTimerPublisher
     */
    private $timerPublisher;

    /**
     * @var ClockInterface
     */
    private $clock;
    /**
     * @var TimerStorageInterface
     */
    private $timerStorage;

    public function __construct(
        MessageBusTimerPublisher $timerPublisher,
        ClockInterface $clock,
        TimerStorageInterface $timerStorage
    ) {
        parent::__construct();
        $this->timerPublisher = $timerPublisher;
        $this->clock = $clock;
        $this->timerStorage = $timerStorage;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Timer Processor');

        $options = new PollingTimerProcessorOptions();
        $options->withName('main');
        $options->withMaximumProcessingTime(PollingTimerProcessorOptions::INFINITE);
        $processor = new PollingTimerProcessor($this->clock, $this->timerPublisher, $this->timerStorage, $options);

        $io->writeln('Timer Processor Started.');
        $processor->start();

        $io->writeln('Timer Processor Stopped.');

        return self::SUCCESS;
    }

    protected function onInterruption($input, $output): void
    {
        $output->writeln('Timer Processor Stopping ...');
    }
}
