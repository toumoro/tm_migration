<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Toumoro\TmMigration\Service\SQLMigrationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SQLMigrationServiceTest extends FunctionalTestCase
{
    protected $queries;

    protected function setUp(): void
    {
        parent::setUp();

        $sqlFile = __DIR__ . '/../Fixtures/queries_to_migrate.sql';
        $sqlContent = file_get_contents($sqlFile);

        if ($sqlContent === false) {
            throw new FileNotFoundException('SQL fixture file not found or could not be read.');
        }

        $this->queries = array_filter(array_map('trim', explode(';', $sqlContent)));
    }

    #[Test]
    public function checkSqlQueriesMigration(): void
    {
        $service = GeneralUtility::makeInstance(SQLMigrationService::class);
        $executedCount = $service->migrate($this->queries);

        self::assertSame(count($this->queries), $executedCount, 'All SQL queries should be executed.');

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName('Default');

        $result = $connection->fetchOne('SELECT COUNT(*) FROM tx_tmexample_table');
        self::assertGreaterThan(0, $result, 'Data should be inserted into tx_tmexample_table');
    }
}
