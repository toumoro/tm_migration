<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Unit\Upgrades;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Toumoro\TmMigration\Upgrades\GridElementsToContainerUpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

final class GridElementsToContainerUpgradeWizardTest extends TestCase
{
    private GridElementsToContainerUpgradeWizard $subject;
    private QueryBuilder&MockObject $queryBuilderMock;
    private ConnectionPool&MockObject $connectionPoolMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);

        $restrictionMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $restrictionMock->method('removeAll')->willReturnSelf();
        $restrictionMock->method('add')->willReturnSelf();

        $this->queryBuilderMock
            ->method('getRestrictions')
            ->willReturn($restrictionMock);

        $this->connectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->willReturn($this->queryBuilderMock);

        GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPoolMock);

        $this->subject = new GridElementsToContainerUpgradeWizard($this->connectionPoolMock);
    }

    #[Test]
    public function isUpgradeWizard(): void
    {
        self::assertInstanceOf(UpgradeWizardInterface::class, $this->subject);
    }

    #[Test]
    public function hasTitle(): void
    {
        self::assertSame('Migrate Gridelements to Container', $this->subject->getTitle());
    }

    #[Test]
    public function hasDescription(): void
    {
        self::assertSame(
            'This update wizard switches from EXT:gridelements to EXT:container.',
            $this->subject->getDescription()
        );
    }

    #[Test]
    public function testExecuteUpdateProcessesFoundGridelements(): void
    {
        $gridElement = [
            'uid' => 1,
            'tx_gridelements_backend_layout' => 'container_2col',
            'pi_flexform' => '<T3FlexForms/>',
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([$gridElement]);

        $this->queryBuilderMock
            ->method('select')->willReturn($this->queryBuilderMock);
        $this->queryBuilderMock
            ->method('from')->willReturn($this->queryBuilderMock);
        $this->queryBuilderMock
            ->method('where')->willReturn($this->queryBuilderMock);
        $this->queryBuilderMock
            ->method('executeQuery')->willReturn($result);

        $wizard = $this->getMockBuilder(GridElementsToContainerUpgradeWizard::class)
            ->setConstructorArgs([$this->connectionPoolMock])
            ->onlyMethods(['updateContainer', 'updateChildren'])
            ->getMock();

        $wizard->expects(self::once())
            ->method('updateContainer')
            ->with(1, 'container_2col', '<T3FlexForms/>');

        $wizard->expects(self::once())
            ->method('updateChildren')
            ->with(1);

        self::assertTrue($wizard->executeUpdate());
    }
}
