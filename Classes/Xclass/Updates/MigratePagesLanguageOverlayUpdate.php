<?php
declare(strict_types = 1);

namespace Toumoro\TmMigration\Xclass\Updates;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\CMS\v95\Install\Updates\MigratePagesLanguageOverlayUpdate as BaseMigratePagesLanguageOverlayUpdate;

/**
 * Merge pages_language_overlay rows into pages table
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('pagesLanguageOverlay')]
class MigratePagesLanguageOverlayUpdate extends BaseMigratePagesLanguageOverlayUpdate
{
    /**
     * Performs the update.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        // Warn for TCA relation configurations which are not migrated.
        if (isset($GLOBALS['TCA']['pages_language_overlay']['columns'])
            && is_array($GLOBALS['TCA']['pages_language_overlay']['columns'])
        ) {
            foreach ($GLOBALS['TCA']['pages_language_overlay']['columns'] as $fieldName => $fieldConfiguration) {
                if (isset($fieldConfiguration['config']['MM'])) {
                    $this->output->writeln('The pages_language_overlay field ' . $fieldName
                        . ' with its MM relation configuration can not be migrated'
                        . ' automatically. Existing data relations to this field have'
                        . ' to be migrated manually.');
                }
            }
        }

        // Ensure pages_language_overlay is still available in TCA
        GeneralUtility::makeInstance(LoadTcaService::class)->loadExtensionTablesWithoutMigration();
        $this->mergePagesLanguageOverlayIntoPages();
        $this->updateInlineRelations();
        $this->updateFalFileReferences();
        $this->updateSysHistoryRelations();
        return true;
    }

    protected function updateFalFileReferences(): void
    {
        $translatedPagesQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $translatedPagesQueryBuilder->getRestrictions()->removeAll();
        $translatedPagesRows = $translatedPagesQueryBuilder
            ->select('uid', 'legacy_overlay_uid')
            ->from('pages')
            ->where(
                $translatedPagesQueryBuilder->expr()->gt(
                    'l10n_parent',
                    $translatedPagesQueryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            )
            ->executeQuery();

        while ($translatedPageRow = $translatedPagesRows->fetchAssociative()) {
            $sysFileRefQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $sysFileRefQueryBuilder->getRestrictions()->removeAll();
            $references = $sysFileRefQueryBuilder
                ->select('*')
                ->from('sys_file_reference')
                ->where(
                    $sysFileRefQueryBuilder->expr()->eq(
                        'uid_foreign',
                        $sysFileRefQueryBuilder->createNamedParameter($translatedPageRow['legacy_overlay_uid'], ParameterType::INTEGER)
                    ),
                    $sysFileRefQueryBuilder->expr()->eq(
                        'tablenames',
                        $sysFileRefQueryBuilder->createNamedParameter('pages_language_overlay')
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($references as $ref) {
                // Duplique la relation avec le nouveau uid et table
                $ref['uid'] = null; // pour qu'un nouvel ID soit généré
                $ref['uid_foreign'] = $translatedPageRow['uid'];
                $ref['tablenames'] = 'pages';
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_reference');
                $connection->insert('sys_file_reference', $ref);
            }
        }
    }
}