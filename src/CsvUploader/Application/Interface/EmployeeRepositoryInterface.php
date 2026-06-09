<?php

declare(strict_types=1);

namespace App\CsvUploader\Application\Interface;

use App\CsvUploader\Application\Dto\EmployeeDto;
use App\CsvUploader\Domain\Entity\Employee;
use Doctrine\DBAL\Exception;

/**
 * Persistence port for Employee aggregates.
 */
interface EmployeeRepositoryInterface
{
    public function add(Employee $employee): void;

    public function flushBatch(): void;

    /**
     * @param EmployeeDto[] $EmployeeDTOs
     * @throws Exception
     */
    public function bulkInsertIgnore(array $EmployeeDTOs): int;
}
