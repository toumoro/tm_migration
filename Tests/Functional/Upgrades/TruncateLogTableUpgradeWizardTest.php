<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Toumoro\TmMigration\Upgrades\TruncateLogTableUpgradeWizard;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TruncateLogTableUpgradeWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['core'];
    private TruncateLogTableUpgradeWizard $subject;

    private ExtensionConfiguration&MockObject $extensionConfigurationMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extensionConfigurationMock);

        $this->subject = new TruncateLogTableUpgradeWizard($this->extensionConfigurationMock);

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_log.csv');
    }

    #[Test]
    public function isUpgradeWizard(): void
    {
        self::assertInstanceOf(UpgradeWizardInterface::class, $this->subject);
    }

    #[Test]
    public function hasTitle(): void
    {
        $expected = 'Truncate/Delete entries from Log Table';
        self::assertSame($expected, $this->subject->getTitle());
    }

    #[Test]
    public function hasDescription(): void
    {
        $expected = 'This update wizard truncates/deletes Log entries based on given lifetime.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function updateIsNotNecessaryWhenDisabledInConfiguration(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('tm_migration')
            ->willReturn(['disableTruncateLogUpgradeWizard' => '1']);

        $wizard = new TruncateLogTableUpgradeWizard($this->extensionConfigurationMock);
        self::assertFalse($wizard->updateNecessary());
    }

    #[Test]
    public function updateIsNecessaryWhenEnabledInConfiguration(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('tm_migration')
            ->willReturn(['disableTruncateLogUpgradeWizard' => '0']);

        $wizard = new TruncateLogTableUpgradeWizard($this->extensionConfigurationMock);
        self::assertTrue($wizard->updateNecessary());
    }

    #[Test]
    public function executeUpdateDeletesEntriesOlderThanConfiguredDays(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('tm_migration')
            ->willReturn([
                'disableTruncateLogUpgradeWizard' => false,
                'numberOfDays' => '40',
            ]);

        $wizard = new TruncateLogTableUpgradeWizard($this->extensionConfigurationMock);
        $result = $wizard->executeUpdate();

        self::assertTrue($result);

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_log');
        $count = $connection->count('*', 'sys_log', []);

        self::assertSame(0, $count);
    }
}
