<?php

declare(strict_types=1);

use App\CsvUploader\Infrastructure\Adapter\Persistence\EntityManagerFactory;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables (DATABASE_URL) for the standalone CLI.
(new Dotenv())->bootEnv(__DIR__ . '/.env');

$entityManager = (new EntityManagerFactory(
    databaseUrl: $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'],
    entityPath: __DIR__ . '/src/CsvUploader/Domain/Entity',
    isDevMode: true,
))->create();

$configuration = new ConfigurationArray([
    'migrations_paths' => [
        'App\Migrations' => __DIR__ . '/migrations',
    ],
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
]);

return DependencyFactory::fromEntityManager(
    $configuration,
    new ExistingEntityManager($entityManager),
);
