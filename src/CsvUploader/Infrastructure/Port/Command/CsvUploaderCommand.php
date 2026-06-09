<?php

declare(strict_types=1);

namespace App\CsvUploader\Infrastructure\Port\Command;

use App\CsvUploader\Application\Exception\CsvUploaderException;
use App\CsvUploader\Application\Service\CsvUploaderService;
use App\CsvUploader\Infrastructure\Adapter\File\CsvFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'csv:upload',
    description: 'Read a CSV file, print a CREATE TABLE statement and import the rows.',
)]
final class CsvUploaderCommand extends Command
{
    public function __construct(
        private readonly CsvUploaderService $uploader
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file to process')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'Target table name', 'employees')
            ->addOption('no-persist', null, InputOption::VALUE_NONE, 'Only print the CREATE TABLE statement, do not write to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $file */
        $file = $input->getArgument('file');
        /** @var string $table */
        $table = $input->getOption('table');
        $persist = !$input->getOption('no-persist');

        try {
            $csvFile = new CsvFile($file);

            if (!$persist) {
                $createTableStatement = $this->uploader->generateCreateTableStatement($csvFile, $table);

                $io->section('CREATE TABLE statement');
                $output->writeln($createTableStatement);
                $io->note('Persistence skipped (--no-persist).');

                return Command::SUCCESS;
            }

            $result = $this->uploader->import($csvFile);

            $io->section('Import summary');
            $io->table(
                ['Processed', 'Inserted', 'Skipped (duplicates)'],
                [[$result->processed, $result->inserted, $result->skipped]],
            );
            $io->success('CSV imported successfully.');

            return Command::SUCCESS;
        } catch (CsvUploaderException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $exception) {
            $io->error(\sprintf('Unexpected error: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
