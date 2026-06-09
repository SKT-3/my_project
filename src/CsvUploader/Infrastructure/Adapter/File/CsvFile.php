<?php

declare(strict_types=1);

namespace App\CsvUploader\Infrastructure\Adapter\File;

use App\CsvUploader\Application\Exception\FileNotReadableException;
use App\CsvUploader\Application\Exception\InvalidCsvRowException;
use App\CsvUploader\Application\Interface\CsvSourceInterface;

final readonly class CsvFile implements CsvSourceInterface
{
    public function __construct(
        private string $path,
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escape = '\\',
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @throws FileNotReadableException
     */
    public function assertReadable(): void
    {
        if (!\is_file($this->path) || !\is_readable($this->path)) {
            throw FileNotReadableException::forPath($this->path);
        }
    }

    /**
     * @return list<string>
     *
     * @throws InvalidCsvRowException
     */
    public function header(): array
    {
        $handle = $this->open();

        try {
            $header = $this->readRow($handle);
        } finally {
            \fclose($handle);
        }

        if ($header === null) {
            throw InvalidCsvRowException::emptyHeader();
        }

        return $this->normalizeHeader($header);
    }

    /**
     * @return \Generator<int, array<string, string>>
     *
     * @throws InvalidCsvRowException
     */
    public function rows(): \Generator
    {
        $handle = $this->open();

        try {
            $header = $this->readRow($handle);

            if ($header === null) {
                throw InvalidCsvRowException::emptyHeader();
            }

            $header = $this->normalizeHeader($header);
            $columnCount = \count($header);
            $lineNumber = 1;

            while (($row = $this->readRow($handle)) !== null) {
                ++$lineNumber;

                if ($this->isBlank($row)) {
                    continue;
                }

                if (\count($row) !== $columnCount) {
                    throw InvalidCsvRowException::columnCountMismatch($lineNumber, $columnCount, \count($row));
                }

                /** @var array<string, string> $assoc */
                $assoc = \array_combine($header, $row);

                yield $assoc;
            }
        } finally {
            \fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function open()
    {
        $this->assertReadable();

        $handle = \fopen($this->path, 'r');

        if ($handle === false) {
            throw FileNotReadableException::forPath($this->path);
        }

        return $handle;
    }

    /**
     * @param resource $handle
     *
     * @return list<string>|null
     */
    private function readRow($handle): ?array
    {
        $row = \fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);

        if ($row === false) {
            return null;
        }

        return \array_map(static fn ($value): string => (string) $value, $row);
    }

    /**
     * @param list<string> $header
     *
     * @return list<string>
     */
    private function normalizeHeader(array $header): array
    {
        return \array_map(static fn (string $name): string => \trim($name), $header);
    }

    /**
     * @param list<string> $row
     */
    private function isBlank(array $row): bool
    {
        if ($row === []) {
            return true;
        }

        foreach ($row as $value) {
            if (\trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
