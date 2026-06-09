<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Dto;

/**
 * Outcome of a CSV upload run, returned by the service to the command.
 */
final readonly class ImportResult
{
    public function __construct(
        public string $createTableStatement,
        public bool   $persisted,
        public int    $processed = 0,
        public int    $inserted = 0,
        public int    $skipped = 0,
    ) {
    }
}
