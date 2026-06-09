<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Dto;

use App\CsvUploader\Application\Exception\InvalidCsvRowException;

final class EmployeeDto
{
    public const string COLUMN_NAME = 'Name';
    public const string COLUMN_AGE = 'Age';
    public const string COLUMN_GRADE = 'Grade';
    public const string COLUMN_SALARY = 'Salary';
    private const int SALARY_SCALE = 2;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $grade,
        public readonly string $salary,
    ) {
    }

    /**
     * @param array<string, string> $row
     * @throws InvalidCsvRowException
     */
    public static function fromRow(array $row): self
    {
        $name = \trim(self::require($row, self::COLUMN_NAME));
        $grade = \trim(self::require($row, self::COLUMN_GRADE));
        $ageRaw = \trim(self::require($row, self::COLUMN_AGE));
        $salaryRaw = \trim(self::require($row, self::COLUMN_SALARY));

        if (!\is_numeric($ageRaw)) {
            throw new InvalidCsvRowException(\sprintf('Invalid age "%s": expected an integer.', $ageRaw));
        }

        if (!\is_numeric($salaryRaw)) {
            throw new InvalidCsvRowException(\sprintf('Invalid salary "%s": expected a number.', $salaryRaw));
        }

        return new self(
            name: $name,
            age: (int) $ageRaw,
            grade: $grade,
            salary: \number_format((float) $salaryRaw, self::SALARY_SCALE, '.', ''),
        );
    }

    /**
     * @param array<string, string> $row
     */
    private static function require(array $row, string $column): string
    {
        if (!\array_key_exists($column, $row)) {
            throw InvalidCsvRowException::missingColumn($column);
        }

        return $row[$column];
    }
}
