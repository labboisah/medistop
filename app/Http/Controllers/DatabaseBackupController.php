<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

class DatabaseBackupController extends Controller
{
    public function backup()
    {
        try {
            $backup = $this->createDatabaseBackup();
        } catch (\Throwable $exception) {
            return back()->with('warning', 'Database backup failed: ' . $exception->getMessage());
        }

        if ($backup['success']) {
            return back()->with('success', $backup['message']);
        }

        return back()->with('warning', $backup['message']);
    }

    protected function createDatabaseBackup(): array
    {
        $driver = config('database.default');
        $databaseConfig = config('database.connections.' . $driver, []);
        $databaseName = $databaseConfig['database'] ?? env('DB_DATABASE', 'laravel');
        $backupDirectory = $this->resolveBackupDirectory();

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0777, true) && ! is_dir($backupDirectory)) {
            return [
                'success' => false,
                'message' => 'The backup destination could not be created. Please check the permissions of the selected folder.',
            ];
        }

        $backupFileName = sprintf(
            '%s_backup_%s.sql',
            Str::slug((string) $databaseName ?: 'database'),
            now()->format('Ymd_His')
        );

        $backupPath = rtrim($backupDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $backupFileName;

        switch ($driver) {
            case 'sqlite':
                $dump = $this->dumpSqliteDatabase();

                if ($dump === null) {
                    return [
                        'success' => false,
                        'message' => 'Database backup failed because the SQLite database could not be exported.',
                    ];
                }

                file_put_contents($backupPath, $dump);

                return [
                    'success' => true,
                    'message' => $this->backupSuccessMessage($backupPath, $this->detectExternalDrive() !== null),
                ];

            default:
                $dumpBinary = $this->resolveDumpBinaryForDriver($driver);

                if (! $dumpBinary) {
                    return [
                        'success' => false,
                        'message' => 'Database backup could not run because the database dump utility is not available on this machine.',
                    ];
                }

                $command = $this->buildDumpCommand($driver, $databaseConfig, $dumpBinary, $backupPath, $databaseName);
                exec($command, $output, $exitCode);

                if ($exitCode !== 0 || ! file_exists($backupPath) || filesize($backupPath) === 0) {
                    $details = is_array($output) ? implode(' ', $output) : (string) $output;
                    if (file_exists($backupPath)) {
                        unlink($backupPath);
                    }

                    return [
                        'success' => false,
                        'message' => 'Database backup failed during export. Details: ' . trim($details ?: 'unknown error'),
                    ];
                }

                return [
                    'success' => true,
                    'message' => $this->backupSuccessMessage($backupPath, $this->detectExternalDrive() !== null),
                ];
        }
    }

    protected function resolveBackupDirectory(): string
    {
        $drive = $this->detectExternalDrive();

        if ($drive) {
            return $drive;
        }

        return $this->downloadsDirectory();
    }

    protected function findDrivePathFromOutput(string $output): ?string
    {
        foreach (preg_split('/\r\n|\r|\n/', $output) as $line) {
            $trimmed = trim($line);
            if (! preg_match('/^([A-Za-z]:)/', $trimmed, $matches)) {
                continue;
            }

            $drive = strtoupper($matches[1]);
            if ($drive === 'C:') {
                continue;
            }

            return rtrim($drive, ':') . ':\\';
        }

        return null;
    }

    protected function detectExternalDrive(): ?string
    {
        if (! $this->isWindows()) {
            return null;
        }

        foreach (['wmic logicaldisk get name,drivetype 2>NUL', 'powershell -NoProfile -Command "Get-CimInstance Win32_LogicalDisk | Where-Object { $_.DriveType -in 2,3 } | Select-Object -ExpandProperty DeviceID" 2>NUL'] as $command) {
            exec($command, $output, $exitCode);

            if ($exitCode !== 0 || empty($output)) {
                continue;
            }

            $combined = implode("\n", $output);
            $drive = $this->findDrivePathFromOutput($combined);

            if ($drive) {
                return $drive;
            }
        }

        return null;
    }

    protected function downloadsDirectory(): string
    {
        if ($this->isWindows()) {
            $powerShellPath = trim((string) shell_exec('powershell -NoProfile -Command "[Environment]::GetFolderPath(\'Downloads\')" 2>NUL'));

            if ($powerShellPath !== '') {
                return $powerShellPath;
            }

            $profile = getenv('USERPROFILE') ?: getenv('HOME') ?: sys_get_temp_dir();

            return rtrim($profile, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Downloads';
        }

        $home = getenv('HOME') ?: sys_get_temp_dir();

        return rtrim($home, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Downloads';
    }

    protected function backupSuccessMessage(string $backupPath, bool $usedExternalDrive): string
    {
        if ($usedExternalDrive) {
            return 'Database backup created successfully on the detected external drive: ' . $backupPath;
        }

        return 'No external drive was detected. The database backup was saved to the Downloads folder: ' . $backupPath;
    }

    protected function dumpSqliteDatabase(): ?string
    {
        $pdo = DB::connection()->getPdo();

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll(PDO::FETCH_COLUMN);

        $dump = "-- SQLite database dump\n-- Generated: " . now()->toDateTimeString() . "\n\n";

        foreach ($tables as $table) {
            $schema = $pdo->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = " . $pdo->quote($table))->fetchColumn();
            if ($schema) {
                $dump .= $schema . ";\n\n";
            }

            $rows = $pdo->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $columns = array_keys($row);
                $columnList = implode(', ', array_map(fn ($column) => '"' . str_replace('"', '""', $column) . '"', $columns));
                $values = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return "'" . str_replace("'", "''", (string) $value) . "'";
                }, array_values($row));

                $dump .= 'INSERT INTO "' . str_replace('"', '""', $table) . '" (' . $columnList . ') VALUES (' . implode(', ', $values) . ");\n";
            }

            $dump .= "\n";
        }

        return $dump !== '' ? $dump : "-- No tables were found in the database.\n";
    }

    protected function resolveDumpBinaryForDriver(string $driver): ?string
    {
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $candidates = [
                trim((string) shell_exec('where mysqldump 2>NUL')),
                trim((string) shell_exec('which mysqldump 2>/dev/null')),
            ];

            foreach ($candidates as $candidate) {
                foreach (preg_split('/\r\n|\r|\n/', $candidate) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        return $line;
                    }
                }
            }

            foreach (glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [] as $path) {
                return $path;
            }

            foreach (glob('C:/xampp/mysql/bin/mysqldump.exe') ?: [] as $path) {
                return $path;
            }

            foreach (glob('C:/wamp64/bin/mysql/*/bin/mysqldump.exe') ?: [] as $path) {
                return $path;
            }
        }

        if ($driver === 'pgsql') {
            $paths = [
                trim((string) shell_exec('where pg_dump 2>NUL')),
                trim((string) shell_exec('which pg_dump 2>/dev/null')),
            ];

            foreach ($paths as $candidate) {
                foreach (preg_split('/\r\n|\r|\n/', $candidate) as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        return $line;
                    }
                }
            }
        }

        return null;
    }

    protected function buildDumpCommand(string $driver, array $databaseConfig, string $dumpBinary, string $backupPath, string $databaseName): string
    {
        if ($driver === 'mysql' || $driver === 'mariadb') {
            return sprintf(
                '"%s" --host=%s --port=%s --user=%s --password=%s --databases %s --result-file=%s 2>&1',
                $dumpBinary,
                escapeshellarg($databaseConfig['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($databaseConfig['port'] ?? 3306)),
                escapeshellarg($databaseConfig['username'] ?? 'root'),
                escapeshellarg($databaseConfig['password'] ?? ''),
                escapeshellarg($databaseName),
                escapeshellarg($backupPath)
            );
        }

        if ($driver === 'pgsql') {
            return sprintf(
                '"%s" --dbname=postgresql://%s:%s@%s:%s/%s --file=%s 2>&1',
                $dumpBinary,
                escapeshellarg($databaseConfig['username'] ?? 'postgres'),
                escapeshellarg($databaseConfig['password'] ?? ''),
                escapeshellarg($databaseConfig['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($databaseConfig['port'] ?? 5432)),
                escapeshellarg($databaseName),
                escapeshellarg($backupPath)
            );
        }

        return 'echo "Unsupported database driver for backup." > ' . escapeshellarg($backupPath);
    }

    protected function isWindows(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }
}
