<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Interface;

use App\CsvUploader\Application\Exception\InvalidCsvRowException;

/**
 * A readable CSV source. Implemented by the infrastructure CsvFile adapter.
 * Kept in the application layer so the service depends on an abstraction
 * rather than on a concrete file implementation.
 */
interface CsvSourceInterface
{
    /**
     * @return list<string> The column names from the header row.
     * @throws InvalidCsvRowException
     */
    public function header(): array;

    /**
     * Stream every data row keyed by column name. Implementations must be able
     * to produce a fresh stream on each call (open-on-read).
     *
     * @return \Generator<int, array<string, string>>
     * @throws InvalidCsvRowException
     */
    public function rows(): \Generator;
}
