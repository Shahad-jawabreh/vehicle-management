<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BinlogBackup extends Command
{
    protected $signature = 'backup:binlog';
    protected $description = 'Create full backup initially, then incremental backup using MySQL binary logs';

    public function handle()
    {
        $lastPosition = cache('last_binlog_position');

        $backupDir = storage_path('app/backups/binlog/' . now()->format('Y-m-d'));
        File::ensureDirectoryExists($backupDir);
        if (!$lastPosition) {
            $fullBackupFile = $backupDir . '/full-' . now()->format('H-i-s') . '.sql';
            $this->info('📦 No previous backup found. Creating full backup...');

            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            $command = "mysqldump -h {$host} -u {$username} -p\"{$password}\" {$database} > \"{$fullBackupFile}\"";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('❌ Full backup failed!');
                return Command::FAILURE;
            }

            $size = File::size($fullBackupFile);
            $this->info('✅ Full backup created successfully!');
            $this->info("📁 File: {$fullBackupFile}");
            $this->info("💾 Size: " . $this->formatBytes($size));

            $current = DB::selectOne('SHOW MASTER STATUS');
            if ($current) {
                cache()->forever('last_binlog_position', [
                    'file' => $current->File,
                    'position' => $current->Position
                ]);
            }

            return Command::SUCCESS;
        }

        $current = DB::selectOne('SHOW MASTER STATUS');
        if (!$current) {
            $this->error('❌ Binary logging is not enabled!');
            return Command::FAILURE;
        }

        $binlogFile = $current->File;
        $currentPosition = $current->Position;

        if ($lastPosition['file'] === $binlogFile &&
            $lastPosition['position'] === $currentPosition) {
            $this->info('✅ No new changes detected.');
            return Command::SUCCESS;
        }

        $binlogPath = config('database.connections.mysql.binlog_path', 'C:/xampp/mysql/data');
        $backupFile = $backupDir . '/incremental-' . now()->format('H-i-s') . '.sql';

        $command = $this->buildMysqlbinlogCommand(
            $binlogPath,
            $lastPosition,
            $binlogFile,
            $currentPosition,
            $backupFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('❌ Failed to extract binary log!');
            return Command::FAILURE;
        }

        $allowedTables = ['users', 'companies', 'vehicles','company_zones'];
        $allowedOps = ['insert', 'update'];

        $lines = file($backupFile);
        $filtered = array_filter($lines, function ($line) use ($allowedTables, $allowedOps) {
            $lineLower = strtolower($line);

            // Check operation
            $operationMatch = false;
            foreach ($allowedOps as $op) {
                if (str_contains($lineLower, $op)) {
                    $operationMatch = true;
                    break;
                }
            }
            if (!$operationMatch) return false;

            // Check table
            foreach ($allowedTables as $table) {
                if (str_contains($lineLower, "`$table`")) return true;
            }

            return false;
        });

        if (empty($filtered)) {
            File::delete($backupFile);
            $this->info('⚙️ No relevant changes found — no incremental file created.');
        } else {
            file_put_contents($backupFile, implode('', $filtered));
            $size = File::size($backupFile);
            $this->info('✅ Filtered incremental backup created successfully!');
            $this->info("📁 File: {$backupFile}");
            $this->info("💾 Size: " . $this->formatBytes($size));
        }

        // Save latest position
        cache()->forever('last_binlog_position', [
            'file' => $binlogFile,
            'position' => $currentPosition
        ]);

        $this->info("📊 From: {$lastPosition['file']}:{$lastPosition['position']}");
        $this->info("📍 To: {$binlogFile}:{$currentPosition}");

        return Command::SUCCESS;
    }

    private function buildMysqlbinlogCommand($binlogPath, $lastPos, $currentFile, $currentPos, $outputFile)
    {
        $mysqlbinlog = 'C:/xampp/mysql/bin/mysqlbinlog.exe';
        $database = config('database.connections.mysql.database');

        $cmd = "\"{$mysqlbinlog}\" --database={$database}";

        if ($lastPos['file'] && $lastPos['file'] === $currentFile) {
            $cmd .= " --start-position={$lastPos['position']}";
            $cmd .= " --stop-position={$currentPos}";
        }

        $cmd .= " \"{$binlogPath}/{$currentFile}\" > \"{$outputFile}\"";
        return $cmd;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
