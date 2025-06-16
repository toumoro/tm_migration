<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Upgrades;

use Symfony\Component\Console\Output\OutputInterface;
use Toumoro\TmMigration\Service\SqlMigrationService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Class GridElementsToContainerUpgradeWizard
 */
#[UpgradeWizard('tmMigration_sqlMigrationUpgradeWizard')]
class SqlMigrationUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{

    private const SQL_FILE = 'migration.sql';
    private OutputInterface $output;

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Run custom SQL scripts';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'This update wizard runs SQL scripts from a file called migration.sql';
    }

     /**
     * @inheritDoc
     */
    public function updateNecessary(): bool
    {
        try {
            $fileName = $this->getFileName();

            return file_exists($fileName);
        } catch(FileDoesNotExistException $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }
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
        $success = true;

        $content = file_get_contents($this->getFileName());

        // check if the file is not empty
        if($content) {

            $queries = array_filter(
                array_map(
                    fn($q) => trim($q, " \t\n\r\0\x0B;"),
                    explode(';', $content)
                )
            );

            $sqlMigrationService = GeneralUtility::makeInstance(SqlMigrationService::class);
            $success = $sqlMigrationService->migrate($queries) > 0;
        } else {
            $success = false;
        }
        
        return $success;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    private function getFileName()
    {
        return Environment::getProjectPath() . '/' . self::SQL_FILE;
    }
}