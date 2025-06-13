<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Upgrades;

use Toumoro\TmMigration\Updates\AbstractListTypeToCTypeUpdate;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * Class CTypeToListTypeUpgradeWizard
 */
#[UpgradeWizard('tmMigration_cTypeToListTypeUpgradeWizard')]
final class CTypeToListTypeUpgradeWizard extends AbstractListTypeToCTypeUpdate
{

    private const MAPPING_ARRAY = 'cTypeToListTypeMappingArray';

    public function getTitle(): string
    {
        return 'Migrate plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'This command migrates plugins [list_type] to content elements [ctype].';
    }

    /**
     * This must return an array containing the "list_type" to "CType" mapping
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'new_content_element1',
     *      'pi_plugin2' => 'new_content_element2',
     *  ]
     *
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {   
        /** @var ExtensionConfiguration $extensionConfiguration */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        $emConfiguration = $extensionConfiguration->get('tm_migration');

        if(isset($emConfiguration[self::MAPPING_ARRAY])) {
            $tmpArray = explode(',', $emConfiguration[self::MAPPING_ARRAY]);

            foreach ($tmpArray as $item) {
                $tmp = explode(':', $item);

                $cTypeListTypeMappingArray[$tmp[0]] = $tmp[1];
            }

            return $cTypeListTypeMappingArray;
        }

        return [];
    }
}