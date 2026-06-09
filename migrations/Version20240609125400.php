<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240609125400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create employees table with indexes on all fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS employees (
            id CHAR(36) NOT NULL,
            name VARCHAR(255) NOT NULL,
            age INT NOT NULL,
            grade VARCHAR(50) NOT NULL,
            salary DECIMAL(10, 2) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_employees_natural ON employees (name, age, grade, salary)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_employees_dates ON employees (created_at, updated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS employees');
    }
}
