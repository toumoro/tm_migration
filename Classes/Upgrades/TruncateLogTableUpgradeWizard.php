<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Upgrades;

use Doctrine\DBAL\Exception as DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Toumoro\TmMigration\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Class TruncateLogTableUpgradeWizard
 */
#[UpgradeWizard('tmMigration_trucateLogTableUpgradeWizard')]
final class TruncateLogTableUpgradeWizard implements UpgradeWizardInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const LOG_TABLE = 'sys_log';
    private const DATE_FIELD = 'tstamp';

    /**
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Truncate/Delete entries from Log Table';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'This update wizard truncates/deletes Log entries based on given lifetime.';
    }

    /**
     * @return bool True if there are records to update
     */
    public function updateNecessary(): bool
    {
        return !ConfigurationUtility::isDisableTruncateLogUpgradeWizard();
    }

    /**
     * Performs the configuration update.
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        return $this->deleteLogTable();
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function deleteLogTable(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::LOG_TABLE);
        $queryBuilder->delete(self::LOG_TABLE);

        $emConfiguration = $this->extensionConfiguration->get('tm_migration');

        if ($emConfiguration['numberOfDays']) {
            $numberOfDays = $emConfiguration['numberOfDays'];
            $deleteTimestamp = strtotime('-' . $numberOfDays . 'days');

            $queryBuilder->where(
                $queryBuilder->expr()->lt(
                    self::DATE_FIELD,
                    $queryBuilder->createNamedParameter($deleteTimestamp, Connection::PARAM_INT)
                )
            );
        }

        try {
            $queryBuilder->executeStatement();
        } catch (DBALException $e) {
            throw new \RuntimeException(self::class . ' failed for table ' . self::LOG_TABLE . ' with error: ' . $e->getMessage(), 1308255491);
        }

        return true;
    }
}
