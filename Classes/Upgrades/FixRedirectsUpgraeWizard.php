<?php
declare(strict_types = 1);

namespace Toumoro\TmMigration\Upgrades;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;

/**
 * Class TruncateLogTableUpgradeWizard
 */
#[UpgradeWizard('tmMigration_fixRedirectsUpgraeWizard')]
final class FixRedirectsUpgraeWizard implements UpgradeWizardInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const REDIRECT_TABLE = 'sys_redirect';
    private const DEFAULT_STATUS_CODE = 307;

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Repair Invalid Redirects';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'This upgrade wizard identifies and corrects invalid or outdated redirect entries in the database.';
    }

    /**
     * @return bool True if there are records to update
     */
    public function updateNecessary(): bool
    {
        return (bool) count($this->getBrokenRedirects());
    }

    /**
     * Performs the configuration update.
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        return $this->updateRedirects();
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    private function updateRedirects(): bool
    {
        $redirects = $this->getBrokenRedirects();

        if(!empty($redirects)) {

            foreach ($redirects as $row) {
                $sourcePath = $row['source_path'];
                
                if (strpos($sourcePath, '/') !== 0) {
                    $sourcePath = '/' . ltrim($sourcePath, '/');
                }

                $queryBuilder = $this->getTableConnection();
                $queryBuilder
                    ->update(self::REDIRECT_TABLE)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($row['uid'], ParameterType::INTEGER)
                        )
                    )
                    ->set('source_path', $sourcePath)
                    ->set('target_statuscode', self::DEFAULT_STATUS_CODE)
                    ->executeStatement();
            }

            return true;
        }

        return false;
    }

    private function getTableConnection(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::REDIRECT_TABLE);
    }

    private function getBrokenRedirects(): array
    {
        $queryBuilder = $this->getTableConnection();
        return $queryBuilder
            ->select('uid', 'source_path', 'target_statuscode')
            ->from(self::REDIRECT_TABLE)
            ->where(
                 $queryBuilder->expr()->notLike(
                    'source_path',
                    $queryBuilder->createNamedParameter('/%')
                )
            )
            ->orWhere(
                $queryBuilder->expr()->eq(
                    'target_statuscode', 
                    $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}