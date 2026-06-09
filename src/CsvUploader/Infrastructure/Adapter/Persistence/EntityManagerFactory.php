<?php

declare(strict_types=1);

namespace App\CsvUploader\Infrastructure\Adapter\Persistence;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Ramsey\Uuid\Doctrine\UuidType;

/**
 * Bootstraps a standalone Doctrine EntityManager.
 *
 * DoctrineBundle is not compatible with Symfony 8.1 in this project, so the
 * EntityManager is wired manually as a service through this factory.
 */
final readonly class EntityManagerFactory
{
    public function __construct(
        private string $databaseUrl,
        private string $entityPath,
        private bool   $isDevMode,
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(): EntityManagerInterface
    {
        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [$this->entityPath],
            isDevMode: $this->isDevMode,
        );
        $config->enableNativeLazyObjects(true);

        $parser = new DsnParser([
            'mysql' => 'pdo_mysql',
            'mysqli' => 'mysqli',
            'pgsql' => 'pdo_pgsql',
            'postgres' => 'pdo_pgsql',
            'postgresql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlite3' => 'pdo_sqlite',
        ]);

        $connection = DriverManager::getConnection($parser->parse($this->databaseUrl), $config);

        return new EntityManager($connection, $config);
    }
}
