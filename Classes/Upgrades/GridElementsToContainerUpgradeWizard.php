<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Upgrades;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Class GridElementsToContainerUpgradeWizard
 */
#[UpgradeWizard('tmMigration_gridelementsToContainerUpgradeWizard')]
class GridElementsToContainerUpgradeWizard implements UpgradeWizardInterface
{
    private const TT_CONTENT_TABLE = 'tt_content';

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Migrate Gridelements to Container';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'This update wizard switches from EXT:gridelements to EXT:container.';
    }

    /**
     * @inheritDoc
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        return (bool)$queryBuilder->count('uid')
            ->from(self::TT_CONTENT_TABLE)
            ->where(
                $queryBuilder->expr()->like('CType', '"%gridelements_pi%"')
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @inheritDoc
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder->select('*')
            ->from(self::TT_CONTENT_TABLE)
            ->where(
                $queryBuilder->expr()->like('CType', '"%gridelements_pi%"')
            )
            ->executeQuery()
            ->fetchAllAssociative();


        if($result) {
            foreach ($result as $gridElement) {
                $this->updateContainer($gridElement['uid'], $gridElement['tx_gridelements_backend_layout'], $gridElement['pi_flexform']);
                $this->updateChildren($gridElement['uid']);
            }
        }

        return true;
    }

    protected function updateContainer(int $uid, string $identifier, ?string $flexForm): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        $queryBuilder->update(self::TT_CONTENT_TABLE)
            ->set('CType', $identifier)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
                )
            )
            ->executeStatement();
    }

    protected function updateChildren(int $uid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        $result = $queryBuilder->select('*')
            ->from(self::TT_CONTENT_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_gridelements_container',
                    $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();


        if($result) {
            foreach ($result as $child) {
                $this->updateChild($child['uid'], $child['tx_gridelements_container'], $child['tx_gridelements_columns']);
            }
        }
    }

    protected function updateChild(int $uid, int $parent, int $colPos): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        $queryBuilder->update(self::TT_CONTENT_TABLE)
            ->set('tx_container_parent', $parent)
            ->set('colPos', (100 + $colPos))
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER)
                )
            )
            ->executeStatement();
    }
}