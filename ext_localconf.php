<?php
defined('TYPO3') or die();

call_user_func(function () {
    // Xclass MigratePagesLanguageOverlayUpdate Class
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\v95\Install\Updates\MigratePagesLanguageOverlayUpdate::class] = [
        'className' => \Toumoro\TmMigration\Xclass\Updates\MigratePagesLanguageOverlayUpdate::class,
    ];

    // Xclass WorkspacesNotificationSettingsUpdate Class
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\v76\Install\Updates\WorkspacesNotificationSettingsUpdate::class] = [
        'className' => \Toumoro\TmMigration\Xclass\Updates\WorkspacesNotificationSettingsUpdate::class,
    ];
});