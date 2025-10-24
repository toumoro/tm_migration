<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Toumoro\TmMigration\Service\SQLMigrationService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RunSqlScriptCommand
 */
#[AsCommand(
    name: 'tmupgrade:importsql',
    description: 'Run custom SQL scripts',
)]
final class RunSqlScriptCommand extends Command
{
    protected const FILE_NAME = 'migration.sql';

    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this
            ->setDescription('Run custom SQL scripts.')
            ->addOption('directory', 'd', InputOption::VALUE_OPTIONAL, 'Directory Source')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'File Name', self::FILE_NAME)
            ->setHelp(
                <<<HELP
                Executes an SQL migration script.

                You can specify a custom directory using the --directory (-d) option.
                By default, the command looks for a file named "migration.sql" in the specified or current directory.
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $fileNames = $this->getFileNames($input);

        if (!empty($fileNames)) {
            foreach ($fileNames as $fileName) {
                $content = file_get_contents($fileName);

                // check if the file is not empty
                if ($content) {

                    $queries = array_filter(
                        array_map(
                            fn($q) => trim($q, " \t\n\r\0\x0B;"),
                            explode(';', $content)
                        )
                    );

                    $SQLMigrationService = GeneralUtility::makeInstance(SQLMigrationService::class);
                    $condition = $SQLMigrationService->migrate($queries) > 0;
                    if ($condition) {
                        $success = Command::SUCCESS;
                        $this->io->info($fileName . ' executed with no errors.');
                    } else {
                        $success = Command::FAILURE;
                        $this->io->info($fileName . ' failed to be executed.');
                    }
                } else {
                    $success = Command::FAILURE;
                    $this->io->info($fileName . ' is empty.');
                }
            }
        } else {
            $success = Command::FAILURE;
            $this->io->info('no sql file found !');
        }

        return $success;
    }

    private function getFileNames($input): array
    {
        $file = $input->getOption('file');

        if ($file && file_exists($file)) {

            // If file path is already absolute, use it as-is
            if (str_starts_with($file, '/')) {
                return [$file];
            }

            $fileName = Environment::getProjectPath() . '/' . $file;

            return [$fileName ?? self::FILE_NAME];
        }

        $directory = $input->getOption('directory');

        if ($directory && file_exists($directory)) {
            $files = glob($directory . '/*.sql', GLOB_MARK);

            return $files;
        }

        return [];
    }
}
