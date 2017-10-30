<?php

namespace Replissimo;

use Doctrine\DBAL\Connection;

class DatabaseHelper
{
    private $doctrine;
    private $config;

    public function __construct(Connection $doctrine, array $config)
    {
        $this->doctrine = $doctrine;
        $this->config = $config;
    }

    public function getNewDatabaseName(string $databaseToCopy, string $userName): string
    {
        return "_{$databaseToCopy}_{$userName}";
    }

    public function isDatabaseNameValid(string $database): bool
    {
        return preg_match('/[^a-zA-Z\d_]/', $database);
    }

    public function getAllowedDatabases(): array
    {
        $statement = $this->doctrine->executeQuery(
            'SHOW DATABASES WHERE `Database` NOT IN (?) AND `Database` NOT REGEXP ?',
            [
                $this->config['disallowed_databases'],
                $this->config['internal_databases_format']
            ],
            [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        );
        $databases = $statement->fetchAll();
        return array_column($databases, 'Database');
    }

    public function getInternalDatabases(): array
    {
        $statement = $this->doctrine->executeQuery(
            'SHOW DATABASES WHERE `Database` REGEXP ?',
            [$this->config['internal_databases_format']]
        );
        $databases = $statement->fetchAll();
        return array_column($databases, 'Database');
    }

    public function createDatabase(string $databaseName) 
    {
        $this->doctrine->query('CREATE DATABASE ' . $this->doctrine->quoteIdentifier($databaseName));
        $this->grantPermissions($databaseName);
    }

    private function grantPermissions(string $databaseName)
    {
        $databaseName = $this->doctrine->quoteIdentifier($databaseName);
        $user = $this->doctrine->quote($this->config['user_owner']);

        $permissions = 'SELECT, EXECUTE, SHOW VIEW, ALTER, ALTER ROUTINE, CREATE, CREATE ROUTINE, CREATE VIEW, ' .
            'CREATE TEMPORARY TABLES, DELETE, DROP, EVENT, INDEX, INSERT, REFERENCES, TRIGGER, UPDATE, LOCK TABLES';
        $sql = "GRANT $permissions ON $databaseName.* TO $user@'%'";
        $this->doctrine->query($sql);

        $this->doctrine->query('FLUSH PRIVILEGES');
    }

    public function checkDumpRunning(string $databaseName): bool
    {
        $processes = $this->doctrine->fetchAll('SHOW PROCESSLIST');
        foreach ($processes as $process) {
            if ($process['db'] === $databaseName) {
                return true;
            }
        }

        return false;
    }

    public function dropDatabase(string $databaseName)
    {
        $databaseName = $this->doctrine->quoteIdentifier($databaseName);

        $this->doctrine->query("DROP DATABASE $databaseName");
    }
}
