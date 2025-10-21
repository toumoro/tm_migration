<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Toumoro\TmMigration\Upgrades\CTypeToListTypeUpgradeWizard;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CTypeToListTypeUpgradeWizardTest extends UnitTestCase
{
    private ConnectionPool $connectionPool;
    private ExtensionConfiguration&MockObject $extensionConfiguration;
    private CTypeToListTypeUpgradeWizard $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);

        $this->subject = new CTypeToListTypeUpgradeWizard(
            $this->connectionPool,
            $this->extensionConfiguration
        );
    }

    #[Test]
    public function isUpgradeWizard(): void
    {
        self::assertInstanceOf(UpgradeWizardInterface::class, $this->subject);
    }

    #[Test]
    public function hasTitle(): void
    {
        $expected = 'Migrate plugins to content elements.';
        self::assertSame($expected, $this->subject->getTitle());
    }

    #[Test]
    public function hasDescription(): void
    {
        $expected = 'This command migrates plugins [list_type] to content elements [ctype].';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function testReturnsDefaultMappingWhenNoConfigurationExists(): void
    {
        $this->extensionConfiguration
            ->method('get')
            ->with('tm_migration')
            ->willReturn(null);

        $method = new \ReflectionMethod($this->subject, 'getListTypeToCTypeMapping');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject);

        $expected = [
            'pi_plugin1' => 'new_content_element1',
        ];

        self::assertSame($expected, $result);
    }

    #[Test]
    public function testReturnsCustomMappingWhenConfigurationExists(): void
    {
        $this->extensionConfiguration
            ->method('get')
            ->with('tm_migration')
            ->willReturn([
                'cTypeToListTypeMappingArray' => 'pi_plugin1:new_content_element1,pi_plugin2:new_content_element2',
            ]);

        $method = new \ReflectionMethod($this->subject, 'getListTypeToCTypeMapping');
        $method->setAccessible(true);

        $result = $method->invoke($this->subject);

        $expected = [
            'pi_plugin1' => 'new_content_element1',
            'pi_plugin2' => 'new_content_element2',
        ];

        self::assertSame($expected, $result);
    }
}
