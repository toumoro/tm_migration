<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SQLMigrationService
 */
class SQLMigrationService
{
    /**
     * @param array $queries
     *
     * @return int
     */
    public function migrate(array $queries): int
    {
        $count = 0;

        foreach ($queries as $sql) {

            if (empty(trim($sql))) {
                continue;
            }

            try {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
                $connection->executeStatement($sql);
                $count++;
            } catch (\Exception $e) {
                $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
                $logger->error('SQL migration error : ', [
                    'query' => $sql,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
