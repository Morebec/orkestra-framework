<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Console Command used to start Road Runner in a simplified manner.
 */
class StartRoadRunnerConsoleCommand extends Command implements SignalableCommandInterface
{
    protected static $defaultName = 'orkestra:road-runner:start';

    /** @var string */
    private $projectDir;

    /** @var string */
    private $defaultConfigurationFile;

    /** @var SymfonyStyle */
    private $io;


    public function __construct(ParameterBagInterface $parameterBag)
    {
        set_time_limit(0);
        $this->projectDir = (string)$parameterBag->get('kernel.project_dir');
        $this->defaultConfigurationFile = "{$this->projectDir}/.rr.{$_ENV['APP_ENV']}.yaml";
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'IP address the server should use, overrides the configuration file.')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port number the server should use, overrides the configuration file.')
            ->addOption('rpcPort', null, InputOption::VALUE_OPTIONAL, 'Port for the TCP connection of RPC.')
            ->addOption('healthCheckPort', null, InputOption::VALUE_OPTIONAL, 'Port for the Health Status endpoint')
            ->addOption('config', null, InputOption::VALUE_OPTIONAL, 'Alternative configuration file to use.', $this->defaultConfigurationFile)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Road Runner');


        $configuration = $this->loadConfiguration($input);

        $process = new Process([
            "$this->projectDir/bin/rr", 'serve',
            '-c', $configuration['filename'],
            '-o', "http.address={$configuration['http']['address']}",
            '-o', "status.address={$configuration['status']['address']}",
            '-o', 'logs.output=stdout',
            '-o', 'logs.encoding=json',
            '--dotenv=' . $this->projectDir
        ]);
        $process->setOptions(['create_new_console' => true]);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);

        $this->io->writeln('Starting server ...');
        $process->start();

        $this->io->writeln('Server running with PID: ' . $process->getPid());
        $this->io->writeln([
            '',
            "  Loaded Configuration file: <fg=yellow;options=bold>{$configuration['filename']} </>",
            "  Local address: <fg=white;options=bold>http://{$configuration['http']['address']}</>",
            '',
            "  <fg=yellow>Press Ctrl+C to stop the server</>",
            '',
        ]);


        $process->wait([$this, 'handleServerOutput']);

        $this->io->writeln('Server stopped ...');

        return $process->getExitCode();
    }

    public function loadConfiguration(InputInterface $input): array
    {
        // Config file
        $configOption = $input->getOption('config');
        $configurationFile = $configOption ?: $this->defaultConfigurationFile;

        $config = Yaml::parseFile($configurationFile);
        $config['filename'] = $configurationFile;

        [$configHost, $configPort] = explode(':', $config['http']['address']);
        $host = $input->getOption('host');
        if ($host) {
            $config['http']['address'] = str_replace("{$configHost}:", "{$host}:", $config['http']['address']);
        }


        $port = $input->getOption('port');
        if ($port) {
            $config['http']['address'] = str_replace(":{$configPort}", ":{$port}", $config['http']['address']);
        }

        $healthCheckPort = $input->getOption('healthCheckPort');
        if ($healthCheckPort) {
            [, $configPort] = explode(':', $config['status']['address']);
            $config['status']['address'] = str_replace(":{$configPort}", ":{$healthCheckPort}", $config['status']['address']);
        }

        // dump($config);

        return $config;
    }

    public function handleServerOutput(string $type, string $buffer) {
        $allowDebug = $_ENV['APP_ENV'] === 'dev';

        if ($type === 'err') {
            $this->io->error(str_replace('\t', '\n',$buffer));
            return;
        }

        $lines = explode('\t', $buffer);
        foreach ($lines as $line) {
            $output = json_decode($line, true);

            if(!$output) {
                return;
            }

            $message = "[{$output['T']}][{$output['N']}]: {$output['M']}";
            $logLevel = $output['L'];

            if ($logLevel === 'DEBUG' && $allowDebug) {
                $this->io->text($message);
            } elseif ($logLevel === 'INFO') {
                $this->io->info($message);
            } elseif ($logLevel === 'WARNING') {
                $this->io->warning($message);
            } elseif ($logLevel === 'ERROR') {
                $this->io->error($message);
            } elseif ($logLevel === 'PANIC') {
                $this->io->error($message);
            }
        }
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal): void
    {
        $signalNames = [
            SIGINT => 'SIGINT',
            SIGTERM => 'SIGTERM'
        ];
        $this->io->warning("Received signal: \"$signalNames[$signal]\" Stopping server");
    }
}