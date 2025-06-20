<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class FixDatabaseErrorsCommand
 */
#[AsCommand(
    name: 'tmupgrade:fixdatabaseerrors',
    description: 'Fix database updateschema errors',
)]
final class FixDatabaseErrorsCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setHelp('This command does nothing. It always succeeds.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Fix Database Errors');

        $affectedTables = $this->getAffectedMmTables();
        if ($affectedTables) {
            foreach ($affectedTables as $table => $records) {
                $this->io->writeln("\n" . 'Process table `' . $table . '`:');
                $progressBar = new ProgressBar($output, count($records));
                foreach ($records as $fields) {
                    $sortingFields = $this->getSortingFields($table, $fields);
                    $this->deleteRecords($table, $fields);
                    $this->insertRecord($table, array_merge($fields,$sortingFields));
                    $progressBar->advance();
                }
                $progressBar->finish();
            }
        } else {
            $this->io->info('Nothing to process');
        }
        $this->io->success('finished!');
        return Command::SUCCESS;
    }

    private function getSortingFields(string $table, array $fields): array
    {
        // Insert new record
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $where = [];
        foreach ($fields as $field => $value) {
            $where[] = $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value));
        }
        $result = $queryBuilder
            ->select('sorting','sorting_foreign')
            ->from($table)
            ->where(...$where)
            ->executeQuery();

        if ($result) {
            try {
                $row = $result->fetchAssociative();
            } catch (Exception $exception) {
                $this->logError($exception->getMessage());
            }
        }

        return $row;
    }

    private function deleteRecords(string $table, array $fields): void
    {
        // Delete records
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $where = [];
        foreach ($fields as $field => $value) {
            $where[] = $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value));
        }
        $queryBuilder
            ->delete($table)
            ->where(...$where)
            ->executeStatement();
    }

    private function insertRecord(string $table, array $fields): void
    {
        // Insert new record
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder
            ->insert($table)
            ->values($fields)
            ->executeStatement();
    }

    private function getAffectedMmTables(): array
    {
        $mmTables = $this->getMmTables();
        $affectedTables = [];

        foreach ($mmTables as $mmTable) {
            $result = null;
            if ($this->tableHasMmMatchFields($mmTable) === true) {
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable($mmTable);
                $result = $queryBuilder
                    ->select('uid_local', 'uid_foreign', 'tablenames', 'fieldname')
                    ->from($mmTable)
                    ->groupBy('uid_local', 'uid_foreign', 'tablenames', 'fieldname')
                    ->having('COUNT(uid_local) > 1')
                    ->having('COUNT(uid_foreign) > 1')
                    ->having('COUNT(tablenames) > 1')
                    ->having('COUNT(fieldname) > 1')
                    ->executeQuery();
            } elseif ($this->tableHasMmMatchFields($mmTable) === false) {
                if(in_array($mmTable, ['sys_dmail_ttaddress_category_mm','sys_dmail_feuser_category_mm','sys_dmail_group_category_mm'])){
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($mmTable);
                    $result = $queryBuilder
                        ->select('uid_local', 'uid_foreign', 'tablenames')
                        ->from($mmTable)
                        ->groupBy('uid_local', 'uid_foreign', 'tablenames')
                        ->having('COUNT(uid_local) > 1')
                        ->having('COUNT(uid_foreign) > 1')
                        ->executeQuery();
                }
                else{
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($mmTable);
                    $result = $queryBuilder
                        ->select('uid_local', 'uid_foreign')
                        ->from($mmTable)
                        ->groupBy('uid_local', 'uid_foreign')
                        ->having('COUNT(uid_local) > 1')
                        ->having('COUNT(uid_foreign) > 1')
                        ->executeQuery();
                }
            }

            if ($result) {
                try {
                    $rows = $result->fetchAllAssociative();
                    if ($rows) {
                        $affectedTables[$mmTable] = $rows;
                    }
                } catch (Exception $exception) {
                    $this->logError($exception->getMessage());
                }
            }
        }

        return $affectedTables;
    }

    private function tableHasMmMatchFields(string $table): ?bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $result = $queryBuilder
            ->select('*')
            ->from($table)
            ->executeQuery();

        try {
            while ($row = $result->fetchAssociative()) {
                $keys = array_keys($row);
                if (\in_array('tablenames', $keys, true) && \in_array('fieldname', $keys, true)) {
                    return true;
                }

                $required = ['uid_local', 'uid_foreign', 'sorting', 'sorting_foreign', 'tablenames'];
                $arrayDiff = array_diff($keys, $required);
                if ($arrayDiff) {
                    $this->logError('Table ' . $table . ' has unknown match fields ' . implode(', ', $arrayDiff));
                    return null;
                }
            }
        } catch (Exception $exception) {
            $this->logError($exception->getMessage());
        }
        return false;
    }

    private function getMmTables(): array
    {
        $mmTables = [];
        foreach ($GLOBALS['TCA'] as $table) {
            foreach (($table['columns'] ?? []) as $column) {
                $config = $column['config'] ?? [];
                if ($config) {
                    $mmTable = $config['MM'] ?? '';
                    if ($mmTable) {
                        $mmTables[] = $config['MM'];
                    }
                }
            }
        }
        return array_unique($mmTables);
    }

    private function logError(string $message): void
    {
        $this->io->error($message);
        $this->logger->error($message);
    }
}