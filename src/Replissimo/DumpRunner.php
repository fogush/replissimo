<?php

namespace Replissimo;

class DumpRunner
{
    const LOG_STORAGE = ROOT . 'var/logs/dumps';

    private $databaseHelper;

    public function __construct(DatabaseHelper $databaseHelper)
    {
        $this->databaseHelper = $databaseHelper;
    }

    public function copyDatabase(string $databaseToCopy, string $newDatabase, array $connectionSettings)
    {
        $command = $this->getCommandString($databaseToCopy, $newDatabase, $connectionSettings);

        exec($command);

        return true;
    }

    private function getCommandString(string $databaseToCopy, string $newDatabase, array $connectionSettings): string
    {
        $connectionString = sprintf(
            '-h %s -u %s -p%s',
            $connectionSettings['dbhost'],
            $connectionSettings['user'],
            $connectionSettings['password']
        );

        $databaseToCopy = escapeshellcmd($databaseToCopy);
        $newDatabase = escapeshellcmd($newDatabase);

        $logPath = $this->getLogPath($newDatabase);

        return "mysqldump --max_allowed_packet=512M $connectionString $databaseToCopy | " .
            "mysql --max_allowed_packet=512M $connectionString $newDatabase 2> $logPath";
    }

    public function checkRunning(string $newDatabase): bool
    {
        return $this->databaseHelper->checkDumpRunning($newDatabase);
    }

    public function getLogs(string $database): string
    {
        $logPath = $this->getLogPath($database);
        if (file_exists($logPath)) {
            return file_get_contents($logPath);
        }

        return '';
    }

    private function getLogPath(string $database): string
    {
        return self::LOG_STORAGE . '/' . $database . '.log';
    }
}