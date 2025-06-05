<?php

declare(strict_types=1);

namespace Toumoro\TmMigration\Command;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\CsvUtility;

#[AsCommand(
    name: 'export:ctypeslisttypes',
    description: 'Export CTypes and List Types to JSON or CSV.',
)]
final class ExportCtypeListTypeCommand extends Command
{
    private SymfonyStyle $io;

    protected const TT_CONTENT_TABLE = 'tt_content';
    protected const FIELDS = 'CType,list_type';
    protected const FILE_NAME = 'export.csv';
    protected const FILE_TYPE_JSON = 'json';
    protected const FILE_TYPE_CSV = 'csv';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Export CTypes and List Types to JSON or CSV.');
        $this->addArgument('file', InputArgument::REQUIRED, 'CSV or JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $listTypeAndCTypeArray = $this->getListTypeAndCTypeArray();
        $file = $input->getArgument('file');

        if(strtolower($file) == self::FILE_TYPE_JSON) {
            foreach ($listTypeAndCTypeArray as $row) {
                 if($row['CType'] == 'list' || $row['list_type']) {
                    $tmp[] = $row['list_type'] . ':' . 'new_content_element1';
                }
            }

            $this->io->info('Here is the configuration for List Type to CType Mapping.');
            $this->io->text(implode(',', $tmp));
            echo(PHP_EOL);
        }

        if(strtolower($file) == self::FILE_TYPE_CSV) {
            $contents = $this->export($listTypeAndCTypeArray, self::FIELDS);
            $fileName = $this->getFileName();

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

    private function renderHeader($fields): array
    {
        $fields = explode(',', $fields);
        array_push($fields, 'pids');

        return $fields;
    }

    private function renderContent($row, $fields): array
    {
        $data = [];

        $fields = explode(',', $fields);
        
        foreach ($fields as $field) {
            $data[] = $row[$field];
        }

        $data[] = $row['pids'];

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

    private function getFileName(): string
    {
        return self::FILE_NAME;
    }

    private function logError(string $message): void
    {
        $this->io->error($message);
        $this->logger->error($message);
    }
}