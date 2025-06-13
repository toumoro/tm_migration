<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\CsvUtility;

/**
 * Class ExportCtypeListTypeCommand
 */
#[AsCommand(
    name: 'export:types',
    description: 'Export CTypes and List Types to JSON or CSV.',
)]
final class ExportCtypeListTypeCommand extends Command
{
    private SymfonyStyle $io;

    protected const TT_CONTENT_TABLE = 'tt_content';
    protected const EXCLUDED_TYPES = [ 'div', 'header', 'html', 'image', 'text', 'textmedia', 'textpic', 'uploads' ];
    protected const FIELDS = [
        'CType',
        'list_type',
        'pids'
    ];
    protected const FILE_NAME = 'export.csv';
    protected const FILE_TYPE_JSON = 'json';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Export CTypes and List Types to JSON or CSV.')
            ->addOption('fileName', 'm', InputOption::VALUE_OPTIONAL, 'File Name', self::FILE_NAME)
            ->addOption('fileType', 't', InputOption::VALUE_OPTIONAL, 'File Type', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $listTypeAndCTypeArray = $this->getListTypeAndCTypeArray();
        $fileType = $input->getOption('fileType');

        if($fileType && strtolower($fileType) == self::FILE_TYPE_JSON) {
            $this->exportJson($listTypeAndCTypeArray);
        } else {
            $contents = $this->export($listTypeAndCTypeArray, self::FIELDS);
            $fileName = $this->getFileName($input);

            if (file_exists($fileName)) {
                file_put_contents($fileName, $contents);
                $this->io->info($fileName . ' already existed and was updated.');
            } else {
                file_put_contents($fileName, $contents);
                $this->io->info($fileName . ' is generated successfully.');
            }
        }

        return Command::SUCCESS;
    }

    private function export($list, $fields): string
    {
        $content = CsvUtility::csvValues($this->renderHeader($fields)) . PHP_EOL;

        foreach ($list as $row) {
            $content .= CsvUtility::csvValues($this->renderContent($row, $fields)) . PHP_EOL;
        }

        return $content;
    }

    private function exportJson($list): void
    {
        foreach ($list as $row) {
                if($row['CType'] == 'list' || $row['list_type']) {
                $tmp[] = $row['list_type'] . ':' . 'new_content_element1';
            }
        }

        $this->io->info('Here is the configuration for List Type to CType Mapping.');
        $this->io->text(implode(',', $tmp));
        echo(PHP_EOL);
    }

    private function renderHeader($fields): array
    {
        return array_values($fields);
    }

    private function renderContent($row, $fields): array
    {
        $data = [];
        
        foreach ($fields as $field) {
            if($field == 'pids') {
                $data[] = $row['pids'];
            } else {
                $data[] = $row[$field];
            }
        }

        return $data;
    }

    private function getListTypeAndCTypeArray(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TT_CONTENT_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->selectLiteral('GROUP_CONCAT(DISTINCT pid ORDER BY pid) AS pids')
            ->addSelect('CType', 'list_type')
            ->from(self::TT_CONTENT_TABLE)
            ->where(
                $queryBuilder->expr()->notIn(
                    'CType',
                    $queryBuilder->createNamedParameter(self::EXCLUDED_TYPES, ArrayParameterType::STRING)
                ),
            )
            ->groupBy('CType')
            ->addGroupBy('list_type')
            ->executeQuery();

        if ($result) {
            try {
                $rows = $result->fetchAllAssociative();
            } catch (Exception $exception) {
                $this->logError($exception->getMessage());
            }
        }

        return $rows;
    }

    private function getFileName($input): string
    {
        return $input->getOption('fileName') ?? self::FILE_NAME;
    }

    private function logError(string $message): void
    {
        $this->io->error($message);
        $this->logger->error($message);
    }
}