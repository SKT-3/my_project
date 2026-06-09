<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Exception;

final class InvalidCsvRowException extends CsvUploaderException
{
    public static function columnCountMismatch(int $lineNumber, int $expected, int $actual): self
    {
        return new self(\sprintf(
            'Malformed CSV at line %d: expected %d columns, got %d.',
            $lineNumber,
            $expected,
            $actual,
        ));
    }

    public static function emptyHeader(): self
    {
        return new self('CSV file does not contain a header row.');
    }

    public static function missingColumn(string $column): self
    {
        return new self(\sprintf('Required column "%s" is missing from the CSV header.', $column));
    }
}
