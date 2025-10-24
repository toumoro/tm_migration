<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Toumoro\TmMigration\Command\UpgradeWizardRunCommand;
use TYPO3\CMS\Install\Service\DatabaseUpgradeWizardsService;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UpgradeWizardRunCommandTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string
     */
    private const COMMAND_NAME = 'tmupgrade:run';

    private $lateBootService;
    private $databaseUpgradeWizardsService;
    private $silentConfigurationUpgradeService;

    private UpgradeWizardRunCommand $subject;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tm_migration'] = [
            'cTypeToListTypeMappingArray' => 'pi_plugin1:new_content_element1,pi_plugin2:new_content_element2',
        ];

        $this->lateBootService = $this->createMock(LateBootService::class);
        $this->databaseUpgradeWizardsService = $this->createMock(DatabaseUpgradeWizardsService::class);
        $this->silentConfigurationUpgradeService = $this->createMock(SilentConfigurationUpgradeService::class);

        $mockUpgradeWizardsService = $this->createMock(UpgradeWizardsService::class);

        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')
            ->with(UpgradeWizardsService::class)
            ->willReturn($mockUpgradeWizardsService);

        $this->lateBootService->method('loadExtLocalconfDatabaseAndExtTables')
            ->willReturn($mockContainer);

        $this->databaseUpgradeWizardsService->method('isDatabaseCharsetUtf8')
            ->willReturn(true);

        $this->subject = new UpgradeWizardRunCommand(
            $this->lateBootService,
            $this->databaseUpgradeWizardsService,
            $this->silentConfigurationUpgradeService,
            self::COMMAND_NAME
        );

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
        $expected = 'Run upgrade wizard. Without arguments all available wizards will be run.';
        self::assertSame($expected, $this->subject->getDescription());
    }

    #[Test]
    public function hasHelpText(): void
    {
        $expected = 'This command allows running upgrade wizards on CLI. To run a single wizard add the '
                . 'identifier of the wizard as argument. The identifier of the wizard is the name it is '
                . 'registered with in ext_localconf.';
        self::assertSame($expected, $this->subject->getHelp());
    }

    #[Test]
    public function testExecuteSuccess(): void
    {
        $exitCode = $this->commandTester->execute([]);
        self::assertSame(0, $exitCode);
    }
}
