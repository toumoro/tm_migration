<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Toumoro\TmMigration\Command\FixDatabaseErrorsCommand;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FixDatabaseErrorsCommandTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string
     */
    private const COMMAND_NAME = 'tmupgrade:fixdatabaseerrors';

    private $connectionPool;
    private $logger;
    private $subject;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new FixDatabaseErrorsCommand($this->connectionPool, $this->logger, self::COMMAND_NAME);
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
        $expected = 'Fix database updateschema errors';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function hasHelpText(): void
    {
        $expected = 'This command does nothing. It always succeeds.';
        self::assertSame($expected, $this->subject->getHelp());
    }

    #[Test]
    public function executeWithNoAffectedTablesPrintsInfoAndSucceeds(): void
    {
        $exitCode = $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Nothing to process', $output);
        self::assertStringContainsString('finished!', $output);
    }

    #[Test]
    public function executeWithAffectedTablesProcessesThem(): void
    {
        $exitCode = $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('finished!', $output);
    }
}
