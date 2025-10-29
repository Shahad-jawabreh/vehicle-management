<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IncrementalBackup extends Command
{
    protected $signature = 'backup:incremental';

    public function handle()
    {
        $lastBackupTime = cache('last_backup_time', now()->subYears(10));

        // Check if there are any changes
        if (!$this->hasChanges($lastBackupTime)) {
            $this->info('No changes detected. Skipping backup.');
            return;
        }

        $date = now()->format('Y-m-d');
        $time = now()->format('H-i-s');
        $folderPath = "backups/{$date}";

        // Create backup (only changed data)
        $this->createIncrementalBackup($folderPath, $time, $lastBackupTime);

        cache()->put('last_backup_time', now());
        $this->info('Incremental backup created successfully!');
    }

    private function hasChanges($since)
    {
        // Check if any records were modified
        $tables = $this->getTablesWithTimestamps();

        foreach ($tables as $table) {
            $count = DB::table($table)
                ->where('updated_at', '>', $since)
                ->orWhere('created_at', '>', $since)
                ->count();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    private function getTablesWithTimestamps()
    {
        // Return array of your tables with timestamps
        return ['users', 'posts', 'orders', /* etc */];
    }
}
