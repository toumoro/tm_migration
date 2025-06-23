<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\SettingsWriteException;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Command\Exception\WizardDoesNotNeedToMakeChangesException;
use TYPO3\CMS\Install\Command\Exception\WizardMarkedAsDoneException;
use TYPO3\CMS\Install\Command\Exception\WizardNotFoundException;
use TYPO3\CMS\Install\Service\DatabaseUpgradeWizardsService;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\Exception\SilentConfigurationUpgradeReadonlyException;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\PrerequisiteCollection;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use Toumoro\TmMigration\Utility\ConfigurationUtility;
use Toumoro\TmMigration\Utility\UpgardeWizardsMappingUtility;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Upgrade wizard command for running wizards
 * 
 * Class UpgradeWizardRunCommand
 */
#[AsCommand(
    name: 'tmupgrade:run',
    description: 'Run upgrade wizards',
)]
final class UpgradeWizardRunCommand extends Command
{
    private const MAX_SILENT_UPGRADE_TRIES = 100;
    private UpgradeWizardsService $upgradeWizardsService;

    /**
     * @var OutputInterface|\Symfony\Component\Console\Style\StyleInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(
        private readonly LateBootService $lateBootService,
        private readonly DatabaseUpgradeWizardsService $databaseUpgradeWizardsService,
        private readonly SilentConfigurationUpgradeService $configurationUpgradeService
    ) {
        parent::__construct(null);
    }

    /**
     * Bootstrap running of upgrade wizard,
     * ensure database is utf-8
     */
    protected function bootstrap(): void
    {
        $this->upgradeWizardsService = $this->lateBootService
            ->loadExtLocalconfDatabaseAndExtTables(false)
            ->get(UpgradeWizardsService::class);
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();
        $this->databaseUpgradeWizardsService->isDatabaseCharsetUtf8()
            ?: $this->databaseUpgradeWizardsService->setDatabaseCharsetUtf8();
        // Ensure SilentConfigurationUpdates are also run on CLI, if necessary. Due to the fact that single silent
        // upgrade tasks throwing a `ConfigurationChangedException` on changes and stopping the execution of following
        // upgrades, we need to handle this in a loop handling this exception. Other errors leading to a direct stop,
        // with an additionally concrete handling for readonly `settings.php`. To be safe against future end-less loops,
        // a max-try check is used.
        $loopSafety = 0;
        do {
            try {
                $this->configurationUpgradeService->execute();
                $success = true;
            } catch (ConfigurationChangedException) {
                // Due to the fact that single silent upgrade tasks emits and stops the upgrade chain, we need to
                // handle this as not successful and continue processing the upgrade chain. Therefore, the loop.
                $success = false;
            } catch (SettingsWriteException $e) {
                // Readonly or not-writable `settings.php`. Throw a more meaning full exception and stop the upgrade.
                throw new SilentConfigurationUpgradeReadonlyException(1688462973, $e);
            }
            $loopSafety++;
        } while ($success === false && $loopSafety < self::MAX_SILENT_UPGRADE_TRIES);
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure()
    {
        $this->setDescription('Run upgrade wizard. Without arguments all available wizards will be run.')
            ->addArgument(
                'wizardName',
                InputArgument::OPTIONAL
            )->setHelp(
                'This command allows running upgrade wizards on CLI. To run a single wizard add the ' .
                'identifier of the wizard as argument. The identifier of the wizard is the name it is ' .
                'registered with in ext_localconf.'
            );
    }

    /**
     * Update language packs of all active languages for all active extensions
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->bootstrap();
        $wizardToExecute = (string)$input->getArgument('wizardName');
        if ($wizardToExecute === '') {
            return $this->runAllWizards();
        }

        try {
            $upgradeWizard = $this->getWizard($wizardToExecute);
        } catch (WizardMarkedAsDoneException|WizardDoesNotNeedToMakeChangesException $e) {
            $this->output->note($e->getMessage());
            return Command::SUCCESS;
        } catch (WizardNotFoundException $e) {
            $this->output->error($e->getMessage());
            return Command::FAILURE;
        }

        $prerequisitesFulfilled = $this->handlePrerequisites([$upgradeWizard]);
        if ($prerequisitesFulfilled === true) {
            return $this->runSingleWizard($upgradeWizard);
        }
        return Command::FAILURE;
    }

    /**
     * Get Wizard instance by class name and identifier
     * Returns null if wizard is already done
     */
    protected function getWizard(string $identifier): UpgradeWizardInterface
    {
        // already done
        if ($this->upgradeWizardsService->isWizardDone($identifier)) {
            throw new WizardMarkedAsDoneException(
                sprintf('Wizard %s already marked as done', $identifier),
                1713880347
            );
        }
        $wizard = $this->upgradeWizardsService->getUpgradeWizard($identifier);
        if ($wizard === null) {
            throw new WizardNotFoundException(
                sprintf('No such wizard: %s', $identifier),
                1713880629
            );
        }

        if ($wizard instanceof ChattyInterface) {
            $wizard->setOutput($this->output);
        }
        if ($wizard->updateNecessary()) {
            return $wizard;
        }

        if (!($wizard instanceof RepeatableInterface)) {
            $this->upgradeWizardsService->markWizardAsDone($wizard);
            throw new WizardMarkedAsDoneException(
                sprintf('Wizard %s does not need to make changes. Marking wizard as done.', $identifier),
                1713880485
            );
        }
        throw new WizardDoesNotNeedToMakeChangesException(
            sprintf('Wizard %s does not need to make changes.', $identifier),
            1713880493
        );
    }

    /**
     * Handles prerequisites of update wizards, allows a more flexible definition and declaration of dependencies
     * Currently implemented prerequisites include "database needs to be up-to-date" and "referenceIndex needs to be up-
     * to-date"
     * At the moment the install tool automatically displays the database updates when necessary but can't do more
     * prerequisites
     *
     * @param UpgradeWizardInterface[] $instances
     */
    protected function handlePrerequisites(array $instances): bool
    {
        $prerequisites = GeneralUtility::makeInstance(PrerequisiteCollection::class);
        foreach ($instances as $instance) {
            foreach ($instance->getPrerequisites() as $prerequisite) {
                $prerequisites->add($prerequisite);
            }
        }
        $result = true;
        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite instanceof ChattyInterface) {
                $prerequisite->setOutput($this->output);
            }
            if (!$prerequisite->isFulfilled()) {
                $this->output->writeln('Prerequisite "' . $prerequisite->getTitle() . '" not fulfilled, will ensure.');
                $result = $prerequisite->ensure();
                if ($result === false) {
                    $this->output->error(
                        '<error>Error running ' .
                        $prerequisite->getTitle() .
                        '. Please ensure this prerequisite manually and try again.</error>'
                    );
                    break;
                }
            } else {
                $this->output->writeln('Prerequisite "' . $prerequisite->getTitle() . '" fulfilled.');
            }
        }
        return $result;
    }

    protected function runSingleWizard(
        UpgradeWizardInterface $instance
    ): int {
        $this->output->title('Running Wizard "' . $instance->getTitle() . '"');
        if ($instance instanceof ConfirmableInterface) {
            $confirmation = $instance->getConfirmation();
            $defaultString = $confirmation->getDefaultValue() ? 'Y/n' : 'y/N';
            $question = new ConfirmationQuestion(
                sprintf(
                    '<info>%s</info>' . LF . '%s' . LF . '%s %s (%s)',
                    $confirmation->getTitle(),
                    $confirmation->getMessage(),
                    $confirmation->getConfirm(),
                    $confirmation->getDeny(),
                    $defaultString
                ),
                $confirmation->getDefaultValue()
            );
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            if (!$helper->ask($this->input, $this->output, $question)) {
                if ($confirmation->isRequired()) {
                    $this->output->error('You have to acknowledge this wizard to continue');
                    return Command::FAILURE;
                }
                if ($instance instanceof RepeatableInterface) {
                    $this->output->note('No changes applied.');
                } else {
                    $this->upgradeWizardsService->markWizardAsDone($instance);
                    $this->output->note('No changes applied, marking wizard as done.');
                }
                return Command::SUCCESS;
            }
        }
        if ($instance->executeUpdate()) {
            $this->output->success('Successfully ran wizard ' . $instance->getTitle());
            if (!$instance instanceof RepeatableInterface) {
                $this->upgradeWizardsService->markWizardAsDone($instance);
            }
            return Command::SUCCESS;
        }
        $this->output->error('<error>Something went wrong while running ' . $instance->getTitle() . '</error>');
        return Command::FAILURE;
    }

    /**
     * Get list of registered upgrade wizards.
     *
     * @return int 0 if all wizards were successful, 1 on error
     */
    public function runAllWizards(): int
    {
        $returnCode = Command::SUCCESS;
        $wizardInstances = [];
        foreach ($this->getUpgradeWizardIdentifiers() as $identifier) {
            try {
                $wizardInstances[] = $this->getWizard($identifier);
            } catch (WizardMarkedAsDoneException|WizardDoesNotNeedToMakeChangesException|WizardNotFoundException) {
                // NOOP
            }
        }
        if (count($wizardInstances) > 0) {
            $prerequisitesResult = $this->handlePrerequisites($wizardInstances);
            if ($prerequisitesResult === false) {
                $returnCode = Command::FAILURE;
                $this->output->error('Error handling prerequisites, aborting.');
            } else {
                $this->output->title('Found ' . count($wizardInstances) . ' wizard(s) to run.');
                foreach ($wizardInstances as $wizardInstance) {
                    $result = $this->runSingleWizard($wizardInstance);
                    if ($result > 0) {
                        $returnCode = Command::FAILURE;
                    }
                }
            }
        } else {
            $this->output->success('No wizards left to run.');
        }
        return $returnCode;
    }

    /**
     * Get the list of identifiers for a specfic version
     *
     * @return array identifiers
     */
    private function getUpgradeWizardIdentifiers(): array
    {
        $identifiers = $this->upgradeWizardsService->getUpgradeWizardIdentifiers();

        $excludedIdentifiers = ConfigurationUtility::getUpgradeWizardToExclude();
        $fromVersion = ConfigurationUtility::getUpgradeWizardFromVersion();
        $upgradeWizardsMapping = UpgardeWizardsMappingUtility::getList();

        $excludedWizards = [];

        if($fromVersion) {
            foreach ($upgradeWizardsMapping as $version => $wizards) {
                if (version_compare((string)$version, $fromVersion, '<')) {
                    $excludedWizards = array_merge($excludedWizards, $wizards);
                }
            }
        }

        if(isset($excludedIdentifiers)) {
            try {
                foreach($excludedIdentifiers as $identifier) {
                    if($this->isValid($identifier)) {
                        array_push($excludedWizards, $identifier);
                    }
                }
                
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage(), 1533931000);
            }
        }

        $excludedWizards = array_values(array_unique($excludedWizards));

        return array_diff($identifiers, $excludedWizards);
    }

    /**
     * Check if the excluded identifier is valid
     *
     * @return bool false if not valid, true if valid
     */
    private function isValid($identifier)
    {
        $identifiers = $this->upgradeWizardsService->getUpgradeWizardIdentifiers();

        return in_array($identifier, $identifiers) ?? false;
    }
}
