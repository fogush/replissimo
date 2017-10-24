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
        $this->removeOldRunLog($newDatabase);

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

        $logPath = $this->getRunLogPath($newDatabase);

        $preventWarning = '2>&1 | grep -a -v "Using a password"';
        return "mysqldump --max_allowed_packet=512M $connectionString $databaseToCopy $preventWarning | " .
            "mysql --max_allowed_packet=512M $connectionString $newDatabase $preventWarning > $logPath &";
    }

    public function isDumpRunning(string $newDatabase): bool
    {
        return $this->databaseHelper->checkDumpRunning($newDatabase);
    }

    public function getRunLogs(string $database): string
    {
        $logPath = $this->getRunLogPath($database);
        if (file_exists($logPath)) {
            return file_get_contents($logPath);
        }

        return '';
    }

    private function removeOldRunLog(string $databaseName)
    {
        $logPath = $this->getRunLogPath($databaseName);
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    private function getRunLogPath(string $database): string
    {
        return self::LOG_STORAGE . '/' . $database . '.log';
    }
}
