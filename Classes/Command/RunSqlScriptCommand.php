<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Core\Environment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Toumoro\TmMigration\Service\SqlMigrationService;

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
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'File Name', self::FILE_NAME)
            ->setHelp('This command execute an SQL script with a file name as parameter. Default file name is migration.sql');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $fileName = $this->getFileName($input);
        $content = file_get_contents($fileName);

        // check if the file is not empty
        if($content) {

            $queries = array_filter(
                array_map(
                    fn($q) => trim($q, " \t\n\r\0\x0B;"),
                    explode(';', $content)
                )
            );

            $sqlMigrationService = GeneralUtility::makeInstance(SqlMigrationService::class);
            $condition = $sqlMigrationService->migrate($queries) > 0;
            if($condition){
                $success = Command::SUCCESS;
                $this->io->info($fileName . ' executed with no errors.');
            }
            else{
                $success = Command::FAILURE;
                $this->io->info($fileName . ' failed to be executed.');
            } 
        } 
        else {
            $this->io->info($fileName . ' is empty.');
            $success = Command::SUCCESS;
        }

        return $success;
    }

    private function getFileName($input): string
    {
        return Environment::getProjectPath() . '/' .$input->getOption('file') ?? self::FILE_NAME;
    }

}