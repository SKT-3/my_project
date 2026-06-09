<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Exception;

/**
 * Base type for every exception thrown by the CsvUploader module.
 * Allows callers to catch the whole module with a single catch block.
 */
abstract class CsvUploaderException extends \RuntimeException
{
}
