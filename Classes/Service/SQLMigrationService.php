<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Class SQLMigrationService
 */
class SQLMigrationService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LogManager $logManager
    ) {}
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
                $connection = $this->connectionPool->getConnectionByName('Default');
                $connection->executeStatement($sql);
                $count++;
            } catch (\Exception $e) {
                $logger = $this->logManager->getLogger(__CLASS__);
                $logger->error('SQL migration error : ', [
                    'query' => $sql,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
