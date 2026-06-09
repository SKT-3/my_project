<?php

declare(strict_types=1);

namespace App\CsvUploader\Infrastructure\Adapter\Repository;

use App\CsvUploader\Application\Dto\EmployeeDto;
use App\CsvUploader\Application\Interface\EmployeeRepositoryInterface;
use App\CsvUploader\Domain\Entity\Employee;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Doctrine ORM implementation of the Employee persistence port.
 *
 * Uses the classic batch-processing pattern: entities are staged with persist()
 * and periodically flushed and detached (clear) so the unit of work never grows
 * unbounded while importing very large files.
 */
final class EmployeeRepository implements EmployeeRepositoryInterface
{
    private int $pending = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly int $batchSize = 500,
    ) {
    }

    /**
     * @param array<EmployeeDto> $EmployeeDTOs
     *
     * @throws Exception
     */
    public function bulkInsertIgnore(array $EmployeeDTOs): int
    {
        if ($EmployeeDTOs === []) {
            return 0;
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $now = new \DateTimeImmutable();
        $nowStr = $now->format('Y-m-d H:i:s');

        $values = [];
        $placeholders = [];

        foreach ($EmployeeDTOs as $dto) {
            $uuid = Uuid::uuid7();
            $values[] = $uuid->toString();
            $values[] = $dto->name;
            $values[] = $dto->age;
            $values[] = $dto->grade;
            $values[] = $dto->salary;
            $values[] = $nowStr;
            $values[] = $nowStr;
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?)';
        }

        $sql = match ($platform::class) {
            \Doctrine\DBAL\Platforms\SQLitePlatform::class => 'INSERT OR IGNORE INTO employees (id, name, age, grade, salary, created_at, updated_at) VALUES ' . implode(', ', $placeholders),
            \Doctrine\DBAL\Platforms\MySQLPlatform::class => 'INSERT IGNORE INTO employees (id, name, age, grade, salary, created_at, updated_at) VALUES ' . implode(', ', $placeholders),
            default => throw new \RuntimeException("Unsupported database platform: " . $platform::class),
        };

        return (int) $connection->executeStatement($sql, $values);
    }

    public function add(Employee $employee): void
    {
        $this->entityManager->persist($employee);
        ++$this->pending;

        if ($this->pending >= $this->batchSize) {
            $this->flushBatch();
        }
    }

    public function flushBatch(): void
    {
        if ($this->pending === 0) {
            return;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->pending = 0;
    }
}
