# CSV Uploader

A Symfony CLI application for importing CSV files into a SQLite database with schema inference and duplicate handling.

## Features

- **Schema Inference**: Automatically generates CREATE TABLE statements by analyzing CSV data types
- **Bulk Import**: Efficient bulk insert with `INSERT OR IGNORE` for duplicate handling
- **Memory Efficient**: Generator-based streaming for processing large files without memory bloat
- **Duplicate Handling**: Skips duplicate rows based on natural key (name, age, grade, salary)
- **UUID Primary Keys**: Uses UUID v7 for employee records

## Database Setup

### SQLite (Default)

The application uses SQLite by default. No additional setup required - the database file is created automatically in `var/data.db`.

To configure SQLite, ensure your `.env` file contains:

```env
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### Manual Table Creation (Optional)

If you need to create the table manually without migrations:

```bash
sqlite3 var/data.db "CREATE TABLE employees (
    id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    grade VARCHAR(50) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(id)
);"

sqlite3 var/data.db "CREATE UNIQUE INDEX IF NOT EXISTS uniq_employees_natural ON employees (name, age, grade, salary);"
```

## Usage

### Generate CREATE TABLE Statement

To generate a SQL CREATE TABLE statement from a CSV file without importing data:

```bash
php bin/console csv:upload data/employees.csv --no-persist
```

This will output the inferred schema based on the CSV data types.

### Import CSV Data

To import CSV data into the database:

```bash
php bin/console csv:upload data/employees.csv
```

This will:
- Process the CSV file in batches of 500 rows
- Insert new records using bulk INSERT
- Skip duplicates based on the natural key (name, age, grade, salary)
- Display a summary of processed, inserted, and skipped rows

### Custom Table Name

To specify a custom table name:

```bash
php bin/console csv:upload data/employees.csv --table custom_employees
```

## Sample CSV Format

The CSV file should have a header row with column names. Example:

```csv
Name,Age,Grade,Salary
Alice Smith,29,L3,55000.50
Bob Johnson,34,L4,62000
Charlie Lee,41,L5,72000.75
```

## Architecture

The application follows Domain-Driven Design principles:

- **Domain Layer**: Entity definitions (`Employee`)
- **Application Layer**: DTOs, services, interfaces
- **Infrastructure Layer**: Repository implementations, file adapters, console commands

## Elasticsearch

ElasticSearch JSON Files:

1. employee_mapping.json - Index Schema Definition
2. sample_employee_document.json - Example Document

Event Sourcing Pattern

User Update → SQL Transaction → Commit → Messenger Message → Worker → ElasticSearch


## Running Analysis

Run PHPStan static analysis:

```bash
php -d memory_limit=512M vendor/bin/phpstan analyse src/CsvUploader --level 8
```
