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
        self::assertSame(307, (int)$redirectsByUid[1]['target_statuscode']);
        self::assertSame('/target1', $redirectsByUid[1]['target']);

        // 2. wrong-status-code
        self::assertSame('/wrong-status-code', $redirectsByUid[2]['source_path']);
        self::assertSame(307, (int)$redirectsByUid[2]['target_statuscode']);
        self::assertSame('/target2', $redirectsByUid[2]['target']);

        // 3. already-correct
        self::assertSame('/already-correct', $redirectsByUid[3]['source_path']);
        self::assertSame(301, (int)$redirectsByUid[3]['target_statuscode']);
        self::assertSame('/target3', $redirectsByUid[3]['target']);

        // 4. regex pattern
        self::assertSame('^products/(.*)$', $redirectsByUid[4]['source_path']);
        self::assertSame(301, (int)$redirectsByUid[4]['target_statuscode']);
        self::assertSame('target4', $redirectsByUid[4]['target']);

        // 5. external URL
        self::assertSame('https://www.google.com', $redirectsByUid[5]['source_path']);
        self::assertSame(301, (int)$redirectsByUid[5]['target_statuscode']);
        self::assertSame('target5', $redirectsByUid[5]['target']);
    }
}
