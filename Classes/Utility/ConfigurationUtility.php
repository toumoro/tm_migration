<?php
declare(strict_types = 1);

namespace Toumoro\TmMigration\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationUtility
 */
abstract class ConfigurationUtility
{

    /**
     * Get extension configuration from LocalConfiguration.php
     *
     * @return array
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected static function getExtensionConfiguration(): array
    {
        return (array)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tm_migration');
    }
    
    public static function isDisableTruncateLogUpgradeWizard(): bool
    {
        $configuration = self::getExtensionConfiguration();

        return $configuration['disableTruncateLogUpgradeWizard'] === '1';
    }
    
    public static function getNumberOfDays(): string
    {
        $configuration = self::getExtensionConfiguration();

        return $configuration['numberOfDays'] ?? '';
    }

    public static function getUpgradeWizardToExclude(): array
    {
        $configuration = self::getExtensionConfiguration();

        if(!empty($configuration['upgradeWizards']['exlcuded'])) {
            return explode(',', $configuration['upgradeWizards']['exlcuded']); 
        }

        return [];
    }

    public static function getUpgradeWizardFromVersion(): string
    {
        $configuration = self::getExtensionConfiguration();

        return $configuration['upgradeWizards']['fromVersion'] ?? '';
    }
}