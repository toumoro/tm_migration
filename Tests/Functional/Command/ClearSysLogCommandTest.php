<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Toumoro\TmMigration\Command\ClearSysLogCommand;
use Toumoro\TmMigration\Service\SQLMigrationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ClearSysLogCommandTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string
     */
    private const COMMAND_NAME = 'tmupgrade:clearsyslog';

    private $mockSQLService;

    private ClearSysLogCommand $subject;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSQLService = $this->createMock(SQLMigrationService::class);
        GeneralUtility::addInstance(SQLMigrationService::class, $this->mockSQLService);

        $this->subject = new ClearSysLogCommand(self::COMMAND_NAME);
        $application = new Application();
        $application->add($this->subject);

        $command = $application->find('tmupgrade:clearsyslog');
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
        $expected = 'Clear table sys_log.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function hasHelpText(): void
    {
        $expected = 'This command execute an SQL script that clears the sys_log database table. -d days -l limit.';
        self::assertSame($expected, $this->subject->getHelp());
    }

    #[Test]
    public function testExecuteSuccess()
    {
        $this->mockSQLService
            ->expects(self::once())
            ->method('migrate')
            ->with(self::callback(function ($statements) {
                $sql = $statements[0];
                $this->assertStringContainsString('DELETE FROM sys_log', $sql);
                $this->assertStringContainsString('recuid=0', $sql);
                return true;
            }))
            ->willReturn(1);

        $exitCode = $this->commandTester->execute(['--limit' => 100, '--days' => 7]);
        self::assertSame(0, $exitCode);
    }

    #[Test]
    public function testExecuteFailure()
    {
        $this->mockSQLService
            ->expects(self::once())
            ->method('migrate')
            ->willReturn(0);

        $exitCode = $this->commandTester->execute([]);
        self::assertSame(1, $exitCode);
    }

    #[Test]
    public function testSqlContainsLimitAndTimestamp()
    {
        $days = 3;
        $limit = 50;
        $expectedTimestamp = strtotime('-' . $days . ' days');

        $this->mockSQLService
            ->expects(self::once())
            ->method('migrate')
            ->with(self::callback(function ($statements) use ($limit, $expectedTimestamp) {
                $sql = $statements[0];
                $this->assertStringContainsString('LIMIT ' . $limit, $sql);
                $this->assertStringContainsString((string)$expectedTimestamp, $sql);
                return true;
            }))
            ->willReturn(1);

        $this->commandTester->execute(['--limit' => $limit, '--days' => $days]);
    }
}
