<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Command;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Toumoro\TmMigration\Command\ExportCtypeListTypeCommand;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExportCtypeListTypeCommandTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string
     */
    private const COMMAND_NAME = 'tmexport:types';

    private $connectionPool;
    private $logger;
    private ExportCtypeListTypeCommand $subject;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new ExportCtypeListTypeCommand($this->connectionPool, $this->logger, self::COMMAND_NAME);
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
        $expected = 'Export CTypes and List Types to JSON or CSV.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function executeGeneratesCsvFileSuccessfully(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);
        $restrictionContainer = $this->createMock(DefaultRestrictionContainer::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);

        $this->connectionPool
            ->expects(self::once())
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $queryBuilder->method('getRestrictions')->willReturn($restrictionContainer);
        $restrictionContainer->method('removeAll')->willReturnSelf();

        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $expressionBuilder->method('notIn')->willReturn('not_in_expr');

        $queryBuilder->method('selectLiteral')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('addGroupBy')->willReturnSelf();
        $queryBuilder->method('createNamedParameter')->willReturn('param');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $result->method('fetchAllAssociative')->willReturn([
            ['CType' => 'my_ctype', 'list_type' => 'my_list', 'pids' => '1,2,3'],
        ]);

        $fileName = sys_get_temp_dir() . '/export_test.csv';

        $exitCode = $this->commandTester->execute([
            '--fileName' => $fileName,
        ]);

        self::assertFileExists($fileName);
        self::assertSame(Command::SUCCESS, $exitCode);

        $content = file_get_contents($fileName);
        self::assertStringContainsString('"CType","list_type","pids"', $content);
        self::assertStringContainsString('my_ctype,my_list,1,2,3', str_replace('"', '', $content));

        unlink($fileName);
    }

    #[Test]
    public function executeWithJsonOptionDisplaysListTypeMapping(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $result = $this->createMock(Result::class);
        $restrictionContainer = $this->createMock(DefaultRestrictionContainer::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);

        $this->connectionPool
            ->expects(self::once())
            ->method('getQueryBuilderForTable')
            ->willReturn($queryBuilder);

        $queryBuilder->method('getRestrictions')->willReturn($restrictionContainer);
        $restrictionContainer->method('removeAll')->willReturnSelf();

        $queryBuilder->method('expr')->willReturn($expressionBuilder);
        $expressionBuilder->method('notIn')->willReturn('not_in_expr');

        $queryBuilder->method('selectLiteral')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('addGroupBy')->willReturnSelf();
        $queryBuilder->method('createNamedParameter')->willReturn('param');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $result->method('fetchAllAssociative')->willReturn([
            ['CType' => 'list', 'list_type' => 'plugin_test', 'pids' => '99'],
        ]);

        $exitCode = $this->commandTester->execute([
            '--fileType' => 'json',
        ]);

        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('plugin_test:plugin_test', $output);
        self::assertSame(Command::SUCCESS, $exitCode);
    }
}
