<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Exception;


/**
 * Raised when the import pipeline fails after rows started being persisted.
 * Wraps the original cause so it can be inspected/logged further up the stack.
 */
final class CsvImportFailedException extends CsvUploaderException
{
    public static function wrap(\Throwable $previous): self
    {
        return new self(
            \sprintf('CSV import failed and was rolled back: %s', $previous->getMessage()),
            0,
            $previous,
        );
    }
}
