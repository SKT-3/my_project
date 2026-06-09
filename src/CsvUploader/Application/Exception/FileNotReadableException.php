<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Exception;

final class FileNotReadableException extends CsvUploaderException
{
    public static function forPath(string $path): self
    {
        return new self(\sprintf('CSV file "%s" does not exist or is not readable.', $path));
    }
}
