<?php

namespace App\CsvUploader\Infrastructure\Adapter;

use App\CsvUploader\Infrastructure\Adapter\Transaction\TransactionInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTransaction implements TransactionInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function begin(): void
    {
        $this->entityManager->beginTransaction();
    }

    public function commit(): void
    {
        $this->entityManager->commit();
    }

    public function rollback(): void
    {
        $this->entityManager->rollback();
    }
}
