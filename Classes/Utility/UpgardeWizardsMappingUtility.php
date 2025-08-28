<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Utility;

/**
 * Class UpgardeWizardsMappingUtility
 */
abstract class UpgardeWizardsMappingUtility
{
    private const VERSION_76 = '7.6';
    private const VERSION_87 = '8.7';
    private const VERSION_95 = '9.5';
    private const VERSION_104 = '10.4';
    private const VERSION_11 = '11';
    private const VERSION_12 = '12';

    /**
     * Return updgrade wizards mapping array
     */
    public static function getList(): array
    {
        return [
            self::VERSION_76 => [
                'accessRightParameters',
                'backendUserStartModule',
                'fileListInAccessModuleList',
                'fileListIsStartModule',
                'filesReplacePermission',
                'imageToTextMedia',
                'languageIsoCode',
                'migrateMediaToAssetsForTextMediaCe',
                'migrateShortcutUrlsAgain',
                'pageShortcutParent',
                'tableFlexFormToTtContentFields',
                'textpicToTextMedia',
                'textToTextMedia',
                'workspacesNotificationSettingsUpdate',
            ],
            self::VERSION_87 => [
                'bulletContentElementUpdate',
                'commandLineBackendUserRemovalUpdate',
                'databaseRowsUpdateWizard87',
                'fileReferenceUpdate',
                'fillTranslationSourceField',
                'formLegacyExtractionUpdate',
                'frontendUserImageUpdateWizard',
                'migrateFeSessionDataUpdate',
                'migrateFscStaticTemplateUpdate',
                'sectionFrameToFrameClassUpdate',
                'splitMenusUpdate',
                'uploadContentElementUpdate',
                'wizardDoneToRegistry',
            ],
            self::VERSION_95 => [
                'adminpanelExtension',
                'argon2iPasswordHashes',
                'backendLayoutIcons',
                'backendUsersConfiguration',
                'pagesLanguageOverlayBeGroupsAccessRights',
                'pagesLanguageOverlay',
                'pagesUrltypeField',
                'pagesSlugs',
                'redirects',
                'separateSysHistoryFromLog',
            ],
            self::VERSION_104 => [
                'databaseRowsUpdateWizard104',
                'formFileExtension',
                'migrateFeloginPlugins',
                'migrateFeloginPluginsCtype',
            ],
            self::VERSION_11 => [
                'backendUserLanguage',
                'databaseRowsUpdateWizard',
                'passwordPolicyForFrontendUsersUpdate',
                'shortcutRecordsMigration',
                'svgFilesSanitization',
                'sysLogChannel',
            ],
            self::VERSION_12 => [
                'changeCollationUpdate',
                'removeDuplicateGomapsextMapAddressMms',
                'removeDuplicateSysCategoryRecordMms',
                'removeOrphanedSysCategoryMMRecords',
                'removeOrphanedSysFileMetadatas',
                'removeOrphanedSysFileReferences',
            ],
        ];
    }
}
