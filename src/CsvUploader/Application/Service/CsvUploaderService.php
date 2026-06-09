<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Service;

use App\CsvUploader\Application\Dto\EmployeeDto;
use App\CsvUploader\Application\Dto\ImportResult;
use App\CsvUploader\Application\Exception\CsvImportFailedException;
use App\CsvUploader\Application\Exception\InvalidCsvRowException;
use App\CsvUploader\Application\Interface\CsvSourceInterface;
use App\CsvUploader\Application\Interface\EmployeeRepositoryInterface;

/**
 * Orchestrates the CSV upload use case:
 *  1. reads the header and infers a CREATE TABLE statement from the data,
 *  2. (optionally) streams the rows into the database in a single transaction,
 *     de-duplicating against existing records and within the file itself.
 *
 * All work is generator-driven so very large files keep a flat memory profile.
 */
final readonly class CsvUploaderService
{
    public function __construct(
        private EmployeeRepositoryInterface   $employees,
        private TableSchemaGenerator          $schemaGenerator,
    ) {
    }

    /**
     * @throws InvalidCsvRowException
     */
    public function generateCreateTableStatement(CsvSourceInterface $source, string $tableName): string
    {
        return $this->schemaGenerator->generate(
            $tableName,
            $source->header(),
            $source->rows(),
        );
    }

    /**
     * @throws CsvImportFailedException
     */
    public function import(CsvSourceInterface $source): ImportResult
    {
        $processed = 0;
        $inserted = 0;
        $batch = [];
        $batchSize = 500;

        try {
            foreach ($source->rows() as $row) {
                ++$processed;

                $batch[] = EmployeeDto::fromRow($row);

                if (count($batch) >= $batchSize) {
                    $inserted += $this->employees->bulkInsertIgnore($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $inserted += $this->employees->bulkInsertIgnore($batch);
            }

            return new ImportResult(
                createTableStatement: 'No Create Table Statement',
                persisted: true,
                processed: $processed,
                inserted: $inserted,
                skipped: $processed - $inserted,
            );

        } catch (\Throwable $exception) {
            throw CsvImportFailedException::wrap($exception);
        }
    }
}
