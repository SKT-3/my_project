<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Service;

use App\CsvUploader\Application\Exception\InvalidCsvRowException;

/**
 * Builds a SQL "CREATE TABLE" statement by inferring each column's type from
 * the CSV data. The rows are consumed from a generator, so arbitrarily large
 * files can be inspected without loading them into memory.
 */
final class TableSchemaGenerator
{
    private const VARCHAR_CAP = 255;

    /**
     * @param list<string>                            $columns
     * @param iterable<int, array<string, string>>    $rows
     *
     * @throws InvalidCsvRowException
     */
    public function generate(string $tableName, array $columns, iterable $rows): string
    {
        if ($columns === []) {
            throw InvalidCsvRowException::emptyHeader();
        }

        $stats = [];
        foreach ($columns as $column) {
            $stats[$column] = new ColumnStats();
        }

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $stats[$column]->observe($row[$column] ?? '');
            }
        }

        $definitions = [];
        foreach ($columns as $column) {
            $definitions[] = \sprintf(
                '    %s %s',
                $this->quoteIdentifier($this->toSnakeCase($column)),
                $stats[$column]->toSqlType(self::VARCHAR_CAP),
            );
        }

        return \sprintf(
            "CREATE TABLE %s (\n%s\n);",
            $this->quoteIdentifier($tableName),
            \implode(",\n", $definitions),
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . \str_replace('`', '``', $identifier) . '`';
    }

    private function toSnakeCase(string $input): string
    {
        $input = \preg_replace('/([a-z])([A-Z])/', '$1_$2', $input) ?? $input;
        $input = \preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $input) ?? $input;
        $input = \preg_replace('/[^a-zA-Z0-9]/', '_', $input) ?? $input;
        $input = \strtolower($input);
        $input = \preg_replace('/_+/', '_', $input) ?? $input;

        return \trim($input, '_');
    }
}
