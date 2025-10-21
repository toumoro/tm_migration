<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use Toumoro\TmMigration\Upgrades\FixRedirectsUpgraeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FixRedirectsUpgraeWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];
    private FixRedirectsUpgraeWizard $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new FixRedirectsUpgraeWizard();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_redirect.csv');
    }

    #[Test]
    public function isUpgradeWizard(): void
    {
        self::assertInstanceOf(UpgradeWizardInterface::class, $this->subject);
    }

    #[Test]
    public function hasTitle(): void
    {
        $expected = 'Repair Invalid Redirects';
        self::assertSame($expected, $this->subject->getTitle());
    }

    #[Test]
    public function hasDescription(): void
    {
        $expected = 'This upgrade wizard identifies and corrects invalid or outdated redirect entries in the database.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function testWizardRepairsInvalidRedirects(): void
    {
        $wizard = new FixRedirectsUpgraeWizard();

        self::assertTrue($wizard->updateNecessary());
        $wizard->executeUpdate();

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_redirect');
        $redirects = $connection->select(
            ['uid', 'source_path', 'target_statuscode', 'target'],
            'sys_redirect'
        )->fetchAllAssociative();

        $redirectsByUid = [];
        foreach ($redirects as $row) {
            $redirectsByUid[$row['uid']] = $row;
        }

        // 1. no-leading-slash
        self::assertSame('/no-leading-slash', $redirectsByUid[1]['source_path']);
        // 2. regex pattern
        self::assertSame('^products/(.*)$', $redirectsByUid[2]['source_path']);
        // 3. external URL
        self::assertSame('https://www.google.com', $redirectsByUid[3]['source_path']);
    }
}
