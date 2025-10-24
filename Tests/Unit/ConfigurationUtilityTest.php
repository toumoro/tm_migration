<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Toumoro\TmMigration\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConfigurationUtilityTest extends UnitTestCase
{
    private MockObject $extensionConfigurationMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $this->extensionConfigurationMock);
    }

    #[Test]
    public function testIsDisableTruncateLogUpgradeWizardReturnsTrue(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->with('tm_migration')
            ->willReturn(['disableTruncateLogUpgradeWizard' => '1']);

        self::assertTrue(ConfigurationUtility::isDisableTruncateLogUpgradeWizard());
    }

    #[Test]
    public function testGetUpgradeWizardToExcludeHandlesEmptyConfiguration(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn([]);

        self::assertSame([], ConfigurationUtility::getUpgradeWizardToExclude());
    }

    #[Test]
    public function testGetUpgradeWizardFromVersionReturnsExpectedValue(): void
    {
        $this->extensionConfigurationMock
            ->method('get')
            ->willReturn(['upgradeWizards' => ['fromVersion' => '12.4.0']]);

        self::assertSame('12.4.0', ConfigurationUtility::getUpgradeWizardFromVersion());
    }
}
