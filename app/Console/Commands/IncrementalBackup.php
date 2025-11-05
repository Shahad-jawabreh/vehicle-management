<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IncrementalBackup extends Command
{
    protected $signature = 'backup:incremental {--report : Show detailed performance report}';
    protected $description = 'Create incremental database backup with only changes';

    // Chunk size for processing large tables
    private const CHUNK_SIZE = 5000;

    // Performance tracking
    private array $performanceMetrics = [];
    private float $totalStartTime;

    public function handle()
    {
        $this->totalStartTime = microtime(true);

        $lastBackupTime = cache('last_backup_time', now()->subYears(10));

        // Get all changes since last backup
        $changesStartTime = microtime(true);
        $changes = $this->getChanges($lastBackupTime);
        $this->performanceMetrics['detection_time'] = microtime(true) - $changesStartTime;

        if (empty($changes)) {
            $this->info('No changes detected. Skipping backup.');
            return Command::SUCCESS;
        }

        $date = now()->format('Y-m-d');
        $time = now()->format('H-i-s');
        $folderPath = storage_path("app/backups/{$date}");

        // Create directory if not exists
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        $filename = "backup-{$time}.sql";
        $filepath = "{$folderPath}/{$filename}";

        // Create incremental backup with only changes
        $backupStartTime = microtime(true);
        $this->createIncrementalBackup($filepath, $changes, $lastBackupTime);
        $this->performanceMetrics['backup_time'] = microtime(true) - $backupStartTime;

        cache()->put('last_backup_time', now());

        $changeCount = array_sum($changes);
        $this->performanceMetrics['total_time'] = microtime(true) - $this->totalStartTime;
        $this->performanceMetrics['total_rows'] = $changeCount;
        $this->performanceMetrics['total_tables'] = count($changes);
        $this->performanceMetrics['file_size'] = File::size($filepath);

        // Show summary
        $this->showSummary($date, $filename, $changeCount, count($changes));

        // Show detailed report if requested
        if ($this->option('report')) {
            $this->showPerformanceReport();
        }

        return Command::SUCCESS;
    }

    private function getChanges($since)
    {
        $database = config('database.connections.mysql.database');
        $tables = DB::select('SHOW TABLES');
        $dbName = 'Tables_in_' . $database;
        $tableNames = ['test_indexed', 'test_no_index'];

        $changes = [];

        foreach ($tableNames as $table) {
            try {
                $tableStartTime = microtime(true);

                // Check if table has timestamp columns
                $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
                $columnNames = collect($columns)->pluck('Field')->toArray();

                // Check which timestamp columns exist
                $hasCreatedAt = in_array('created_at', $columnNames);
                $hasUpdatedAt = in_array('updated_at', $columnNames);
                $hasDeletedAt = in_array('deleted_at', $columnNames);

                // Skip if no timestamp columns
                if (!$hasCreatedAt && !$hasUpdatedAt && !$hasDeletedAt) {
                    continue;
                }

                // Check for indexes
                $indexes = $this->getTableIndexes($table);
                $hasIndexes = $this->checkTimestampIndexes($indexes, $hasCreatedAt, $hasUpdatedAt, $hasDeletedAt);

                // Build query dynamically based on available columns
                  $queryStartTime = microtime(true);

    // Option 1: Use UNION for better index usage
    $updatedIds = DB::table($table)
        ->where('updated_at', '>', $since)
        ->pluck('id');

    $createdIds = DB::table($table)
        ->where('created_at', '>', $since)
        ->pluck('id');

    $deletedIds = $hasDeletedAt ?
        DB::table($table)->where('deleted_at', '>', $since)->pluck('id') :
        collect([]);

    $allIds = $updatedIds->merge($createdIds)->merge($deletedIds)->unique();
    $count = $allIds->count();

    $queryTime = microtime(true) - $queryStartTime;
                $tableTime = microtime(true) - $tableStartTime;

                if ($count > 0) {
                    $changes[$table] = $count;

                    // Store table metrics
                    $this->performanceMetrics['tables'][$table] = [
                        'rows' => $count,
                        'detection_time' => $tableTime,
                        'query_time' => $queryTime,
                        'has_indexes' => $hasIndexes,
                        'indexes' => $indexes,
                    ];

                    $indexStatus = $hasIndexes ? '✓ indexed' : '✗ no index';
                    $this->info("Changes detected in {$table}: {$count} rows ({$indexStatus}, {$this->formatTime($queryTime)})");
                }

            } catch (\Exception $e) {
                $this->warn("Skipping table {$table}: " . $e->getMessage());
                continue;
            }
        }

        return $changes;
    }

    private function createIncrementalBackup($filepath, $changes, $since)
    {
        $database = config('database.connections.mysql.database');

        // Create SQL dump content (only changes)
        $sqlContent = "-- MySQL Incremental Backup\n";
        $sqlContent .= "-- Date: " . now()->toDateTimeString() . "\n";
        $sqlContent .= "-- Database: {$database}\n";
        $sqlContent .= "-- Changes since: " . $since->toDateTimeString() . "\n";
        $sqlContent .= "-- Chunk size: " . self::CHUNK_SIZE . "\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Write header to file
        File::put($filepath, $sqlContent);

        foreach ($changes as $table => $count) {
            $tableStartTime = microtime(true);
            $this->info("Processing {$table}...");

            $header = "-- ========================================\n";
            $header .= "-- Table: {$table} ({$count} changed rows)\n";
            $header .= "-- ========================================\n\n";

            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
            $header .= "-- Table structure (for reference)\n";
            $header .= "-- " . str_replace("\n", "\n-- ", $createTable[0]->{'Create Table'}) . "\n\n";

            // Append to file
            File::append($filepath, $header);

            // Get primary key
            $primaryKey = $this->getPrimaryKey($table);

            // Check which timestamp columns exist for this table
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $columnNames = collect($columns)->pluck('Field')->toArray();

            $hasCreatedAt = in_array('created_at', $columnNames);
            $hasUpdatedAt = in_array('updated_at', $columnNames);
            $hasDeletedAt = in_array('deleted_at', $columnNames);

            // Process changed rows in chunks
            $processedRows = 0;
            $chunkCount = 0;
            $chunkStartTime = microtime(true);

            DB::table($table)
                ->where(function($query) use ($since, $hasCreatedAt, $hasUpdatedAt, $hasDeletedAt) {
                    $hasCondition = false;

                    if ($hasUpdatedAt) {
                        $query->where('updated_at', '>', $since);
                        $hasCondition = true;
                    }

                    if ($hasCreatedAt) {
                        if ($hasCondition) {
                            $query->orWhere('created_at', '>', $since);
                        } else {
                            $query->where('created_at', '>', $since);
                            $hasCondition = true;
                        }
                    }

                    if ($hasDeletedAt) {
                        if ($hasCondition) {
                            $query->orWhere('deleted_at', '>', $since);
                        } else {
                            $query->where('deleted_at', '>', $since);
                        }
                    }
                })
                ->orderBy($primaryKey ?? 'id')
                ->chunk(self::CHUNK_SIZE, function($rows) use ($filepath, $table, $primaryKey, &$processedRows, &$chunkCount) {
                    $chunkCount++;
                    $chunkContent = '';

                    foreach ($rows as $row) {
                        $rowArray = (array)$row;
                        $columns = array_keys($rowArray);
                        $values = [];

                        foreach ($rowArray as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }

                        // Use INSERT ... ON DUPLICATE KEY UPDATE for safe incremental restore
                        if ($primaryKey) {
                            $chunkContent .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ")";

                            // Add ON DUPLICATE KEY UPDATE clause
                            $updates = [];
                            foreach ($columns as $col) {
                                if ($col !== $primaryKey) {
                                    $updates[] = "`{$col}` = VALUES(`{$col}`)";
                                }
                            }
                            if (!empty($updates)) {
                                $chunkContent .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
                            }
                            $chunkContent .= ";\n";
                        } else {
                            // No primary key, use regular INSERT
                            $chunkContent .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                        }

                        $processedRows++;
                    }

                    // Append this chunk to file
                    File::append($filepath, $chunkContent);

                    // Show progress every 1000 rows
                    if ($processedRows % 1000 == 0) {
                        $this->info("  → Processed {$processedRows} rows...");
                    }
                });

            $tableTime = microtime(true) - $tableStartTime;
            $avgTimePerRow = $processedRows > 0 ? $tableTime / $processedRows : 0;

            // Update table metrics with backup time
            $this->performanceMetrics['tables'][$table]['backup_time'] = $tableTime;
            $this->performanceMetrics['tables'][$table]['chunks_processed'] = $chunkCount;
            $this->performanceMetrics['tables'][$table]['avg_time_per_row'] = $avgTimePerRow;
            $this->performanceMetrics['tables'][$table]['rows_per_second'] = $processedRows / $tableTime;

            $this->info("  ✓ Completed {$table}: {$processedRows} rows in {$this->formatTime($tableTime)} ({$chunkCount} chunks)");

            // Add spacing between tables
            File::append($filepath, "\n");
        }

        // Write footer
        File::append($filepath, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function getPrimaryKey($table)
    {
        try {
            $keys = DB::select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
            return $keys[0]->Column_name ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getTableIndexes($table)
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
            return collect($indexes)->groupBy('Column_name')->map(function($group) {
                return $group->pluck('Key_name')->toArray();
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function checkTimestampIndexes($indexes, $hasCreatedAt, $hasUpdatedAt, $hasDeletedAt)
    {
        $hasIndex = false;

        if ($hasCreatedAt && isset($indexes['created_at'])) {
            $hasIndex = true;
        }

        if ($hasUpdatedAt && isset($indexes['updated_at'])) {
            $hasIndex = true;
        }

        if ($hasDeletedAt && isset($indexes['deleted_at'])) {
            $hasIndex = true;
        }

        return $hasIndex;
    }

    private function showSummary($date, $filename, $changeCount, $tableCount)
    {
        $this->newLine();
        $this->info(str_repeat('=', 70));
        $this->info('✅ BACKUP COMPLETED SUCCESSFULLY');
        $this->info(str_repeat('=', 70));
        $this->info("📁 File: {$date}/{$filename}");
        $this->info("📊 Total changes: " . number_format($changeCount) . " rows across {$tableCount} tables");
        $this->info("⏱️  Total time: " . $this->formatTime($this->performanceMetrics['total_time']));
        $this->info("🚀 Speed: " . number_format($changeCount / $this->performanceMetrics['total_time'], 2) . " rows/second");
        $this->info(str_repeat('=', 70));
        $this->newLine();
        $this->info("💡 TIP: Run with --report flag for detailed performance analysis");
        $this->info("   Example: php artisan backup:incremental --report");
    }

    private function showPerformanceReport()
    {
        $this->newLine();
        $this->info(str_repeat('=', 70));
        $this->info('📊 DETAILED PERFORMANCE REPORT');
        $this->info(str_repeat('=', 70));
        $this->newLine();

        // Overall metrics
        $this->info('⏱️  TIMING BREAKDOWN:');
        $this->info("   Change Detection: " . $this->formatTime($this->performanceMetrics['detection_time']));
        $this->info("   Backup Writing:   " . $this->formatTime($this->performanceMetrics['backup_time']));
        $this->info("   Total Time:       " . $this->formatTime($this->performanceMetrics['total_time']));
        $this->newLine();

        // Table-by-table analysis
        $this->info('📋 TABLE-BY-TABLE ANALYSIS:');
        $this->newLine();

        $indexedTables = [];
        $nonIndexedTables = [];

        foreach ($this->performanceMetrics['tables'] as $table => $metrics) {
            $hasIndexes = $metrics['has_indexes'];

            if ($hasIndexes) {
                $indexedTables[$table] = $metrics;
            } else {
                $nonIndexedTables[$table] = $metrics;
            }

            $indexStatus = $hasIndexes ? '✓ INDEXED' : '✗ NO INDEX';
            $this->info("Table: {$table} {$indexStatus}");
            $this->info("   Rows:              " . number_format($metrics['rows']));
            $this->info("   Detection Time:    " . $this->formatTime($metrics['detection_time']));
            $this->info("   Query Time:        " . $this->formatTime($metrics['query_time']));
            $this->info("   Backup Time:       " . $this->formatTime($metrics['backup_time']));
            $this->info("   Chunks Processed:  " . $metrics['chunks_processed']);
            $this->info("   Avg Time/Row:      " . number_format($metrics['avg_time_per_row'] * 1000, 4) . " ms");
            $this->info("   Rows/Second:       " . number_format($metrics['rows_per_second'], 2));

            if (!empty($metrics['indexes'])) {
                $this->info("   Indexes:           " . implode(', ', array_keys($metrics['indexes'])));
            }

            $this->newLine();
        }

        // Comparison: Indexed vs Non-Indexed
        if (!empty($indexedTables) && !empty($nonIndexedTables)) {
            $this->info(str_repeat('=', 70));
            $this->info('🔍 INDEX PERFORMANCE COMPARISON:');
            $this->info(str_repeat('=', 70));
            $this->newLine();

            $avgIndexedSpeed = collect($indexedTables)->avg('rows_per_second');
            $avgNonIndexedSpeed = collect($nonIndexedTables)->avg('rows_per_second');
            $speedImprovement = (($avgIndexedSpeed - $avgNonIndexedSpeed) / $avgNonIndexedSpeed) * 100;

            $this->info("📈 INDEXED TABLES (average):");
            $this->info("   Speed: " . number_format($avgIndexedSpeed, 2) . " rows/second");
            $this->newLine();

            $this->info("📉 NON-INDEXED TABLES (average):");
            $this->info("   Speed: " . number_format($avgNonIndexedSpeed, 2) . " rows/second");
            $this->newLine();

            if ($speedImprovement > 0) {
                $this->info("⚡ PERFORMANCE GAIN WITH INDEXES:");
                $this->info("   " . number_format($speedImprovement, 2) . "% FASTER with indexes!");
            } else {
                $this->warn("⚠️  Indexes not showing expected performance gains");
                $this->warn("   This might be due to small dataset size or other factors");
            }
            $this->newLine();

            // Recommendations
            $this->info('💡 RECOMMENDATIONS:');
            foreach ($nonIndexedTables as $table => $metrics) {
                $this->warn("   → Add indexes to '{$table}' on timestamp columns");
                $this->info("      ALTER TABLE `{$table}` ADD INDEX idx_created_at (created_at);");
                $this->info("      ALTER TABLE `{$table}` ADD INDEX idx_updated_at (updated_at);");
            }
        }

        $this->newLine();
        $this->info(str_repeat('=', 70));
    }

    private function formatTime($seconds)
    {
        if ($seconds < 1) {
            return number_format($seconds * 1000, 2) . ' ms';
        }
        return number_format($seconds, 2) . ' sec';
    }

}
