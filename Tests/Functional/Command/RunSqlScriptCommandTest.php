<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Toumoro\TmMigration\Command\RunSqlScriptCommand;
use Toumoro\TmMigration\Service\SQLMigrationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RunSqlScriptCommandTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string
     */
    private const COMMAND_NAME = 'tmupgrade:importsql';

    private RunSqlScriptCommand $subject;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RunSqlScriptCommand(self::COMMAND_NAME);
        $application = new Application();
        $application->add($this->subject);

        $command = $application->find(self::COMMAND_NAME);
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function isConsoleCommand(): void
    {
        self::assertInstanceOf(Command::class, $this->subject);
    }

    #[Test]
    public function hasDescription(): void
    {
        $expected = 'Run custom SQL scripts.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function hasHelpText(): void
    {
        $expected = <<<HELP
                Executes an SQL migration script.

                You can specify a custom directory using the --directory (-d) option.
                By default, the command looks for a file named "migration.sql" in the specified or current directory.
                HELP;
        self::assertSame($expected, $this->subject->getHelp());
    }

    #[Test]
    public function executesSqlFileSuccessfully(): void
    {
        $SQLFile = __DIR__ . '/../Fixtures/Database/queries.sql';

        $mockMigrationService = $this->createMock(SQLMigrationService::class);
        $mockMigrationService->expects(self::once())
            ->method('migrate')
            ->willReturn(4);

        GeneralUtility::addInstance(SQLMigrationService::class, $mockMigrationService);

        $this->commandTester->execute([
            '--file' => $SQLFile,
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('executed with no errors', $output);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    #[Test]
    public function failsIfFileIsEmpty(): void
    {
        $emptySQLFile = __DIR__ . '/../Fixtures/Database/empty.sql';

        $mockMigrationService = $this->createMock(SQLMigrationService::class);
        $mockMigrationService->expects(self::never())->method('migrate');

        GeneralUtility::addInstance(SQLMigrationService::class, $mockMigrationService);

        $this->commandTester->execute([
            '--file' => $emptySQLFile,
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('is empty', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    #[Test]
    public function failsIfNoFileFound(): void
    {
        $nonExistentSQLFile = __DIR__ . '/../Fixtures/Database/nonexistant.sql';

        $mockMigrationService = $this->createMock(SQLMigrationService::class);
        $mockMigrationService->expects(self::never())->method('migrate');

        GeneralUtility::addInstance(SQLMigrationService::class, $mockMigrationService);

        $this->commandTester->execute([
            '--file' => $nonExistentSQLFile,
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('no sql file found', $output);
        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    #[Test]
    public function executesAllSqlFilesFromDirectory(): void
    {
        $SQLDir = __DIR__ . '/../Fixtures/Database/';

        $mockMigrationService = $this->createMock(SQLMigrationService::class);
        $mockMigrationService->expects(self::exactly(1))
            ->method('migrate')
            ->willReturn(4);

        GeneralUtility::addInstance(SQLMigrationService::class, $mockMigrationService);

        $this->commandTester->execute([
            '--directory' => $SQLDir,
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('executed with no errors', $output);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
