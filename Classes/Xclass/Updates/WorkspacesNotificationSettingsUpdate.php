<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Xclass\Updates;

use TYPO3\CMS\v76\Install\Updates\WorkspacesNotificationSettingsUpdate as BaseWorkspacesNotificationSettingsUpdate;

class WorkspacesNotificationSettingsUpdate extends BaseWorkspacesNotificationSettingsUpdate
{
    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}