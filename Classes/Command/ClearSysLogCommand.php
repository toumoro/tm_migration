<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Toumoro\TmMigration\Service\SqlMigrationService;

/**
 * Class ClearSysLogCommand
 */
#[AsCommand(
    name: 'tmupgrade:clearsyslog',
    description: 'Clear table sys_log',
)]
final class ClearSysLogCommand extends Command
{
    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this
            ->setDescription('Clear table sys_log.')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Days')
            ->setHelp('This command execute an SQL script that clears the sys_log database table. -d days -l limit.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $limit = $input->getOption('limit');
        $days = $input->getOption('days');
        $timestamp = $days?strtotime('-'.$days.' days'):'';

        $sqlMigrationService = GeneralUtility::makeInstance(SqlMigrationService::class);
        $statement = " DELETE FROM sys_log WHERE NOT EXISTS
                        (SELECT * FROM sys_history WHERE sys_history.sys_log_uid=sys_log.uid)
                        AND recuid=0 ";
        if($timestamp){
            $statement .= " AND tstamp < ".$timestamp;
        }

        if($limit){
            $statement .= " LIMIT ".$limit;
        }

        $condition = $sqlMigrationService->migrate([$statement]) > 0;
        if($condition){
            $success = Command::SUCCESS;
            $this->io->info('sys_log clear executed with no errors.');
        }
        else{
            $success = Command::FAILURE;
            $this->io->info('sys_log clear failed to be executed.');
        } 

        return $success;
    }
}