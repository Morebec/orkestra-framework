<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\ConsoleCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class OrkestraFrameworkQuickstartConsoleCommand extends Command
{
    protected static $defaultName = 'orkestra:quickstart';

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Orkestra Framework Installation');
        $io->text('Get Started!');
        $io->text('Thank you for choosing Orkestra! This command can be used to install the Orkestra Framework and get started quickly');

        $io->askHidden('Press enter when you are ready, or press CTRL+C to abort');

        // Composer namespaces
        $this->handleComposerNamespaces($io);

        // Preparing env.local
        $this->handleEnvLocal($io);

        $io->newLine(2);
        $io->text('Orkestra Framework was <info>successfully set up</info>!');
        $io->newLine();

        $io->title("What's next?");
        $io->text('Now, you can:');
        $io->newLine();

        $io->listing(['Initialize an empty git repo']);
        $io->listing(['Personalize the environment variables in <fg=yellow>.env.local</>']);
        $io->listing(['Start the RoadRunner server with <fg=yellow>./bin/rr serve -c .rr.dev.yaml --dotenv=.</>']);
        $io->listing(['Read the documentation at https://github.com/Morebec/orkestra-framework']);

        return self::SUCCESS;
    }

    protected function handleComposerNamespaces(SymfonyStyle $io): void
    {
        $namespace = $io->askQuestion(new Question('Enter your src namespace', 'App'));
        // Fix / to \
        $namespace = str_replace('/', '\\', $namespace);
        // Fix \\ to \ then \ to \\
        $namespace = str_replace('\\\\', '\\', $namespace);

        $testNamespace = $io->askQuestion(new Question('Enter your tests namespace', 'Tests\\'.$namespace));

        // Fix / to \
        $testNamespace = str_replace('/', '\\', $testNamespace);
        // Fix \\ to \ then \ to \\
        $testNamespace = str_replace('\\\\', '\\', $testNamespace);

        // Convert \ to composer json
//        $namespace = str_replace("\\", '\\\\', $namespace);
//        $testNamespace = str_replace("\\", '\\\\', $testNamespace);

        if (!str_ends_with($namespace, '\\')) {
            $namespace .= '\\';
        }

        if (!str_ends_with($testNamespace, '\\')) {
            $testNamespace .= '\\';
        }

        $io->text('Updating composer.json ...');
        $composerJson = $this->findComposerJson();
        if (!$composerJson) {
            throw new \RuntimeException('There is no "composer.json" file');
        }

        $composer = json_decode(file_get_contents($composerJson), true);

        if (\array_key_exists($namespace, $composer['autoload']['psr-4'])) {
            $io->note('Namespace already defined, skipping ...');
        } else {
            $composer['autoload']['psr-4'][$namespace] = 'src';
        }

        if (\array_key_exists($testNamespace, $composer['autoload-dev']['psr-4'])) {
            $io->note('Test namespace already defined, skipping ...');
        } else {
            $composer['autoload-dev']['psr-4'][$testNamespace] = 'tests';
        }

        $composerUpdated = json_encode($composer, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        str_replace('\/', '/', $composerUpdated);
        file_put_contents($composerJson, $composerUpdated);
    }

    protected function handleEnvLocal(SymfonyStyle $io): void
    {
        $envFile = $this->findEnvFile();
        $io->text('Creating .env.local ...');
        if ($envFile) {
            $projectDir = \dirname($envFile);
            $localEnvFile = $projectDir.'/.env.local';
            if (!file_exists($localEnvFile)) {
                copy($envFile, $localEnvFile);
            } else {
                $io->note('.env.local already exists, skipping');
            }
        } else {
            $io->warning('No env file found!');
        }
    }

    private function findComposerJson(): ?string
    {
        $maxDepth = 10;

        for ($currentDepth = 0, $location = __DIR__; $currentDepth <= $maxDepth; $currentDepth++, $location .= '/..') {
            // find composer.json in current dir
            $candidate = "$location/composer.json";

            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function findEnvFile(): ?string
    {
        $maxDepth = 10;

        for ($currentDepth = 0, $location = __DIR__; $currentDepth <= $maxDepth; $currentDepth++, $location .= '/..') {
            // find composer.json in current dir
            $candidate = "$location/.env";

            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
