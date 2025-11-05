<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MillionRowSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting 1 MILLION row generation...');
        $this->command->warn('⚠️  This will take 5-15 minutes. Please wait...');

        // Disable foreign key checks and logging for speed
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('SET UNIQUE_CHECKS=0;');
        DB::statement('SET AUTOCOMMIT=0;');

        $startTime = microtime(true);

        // Create test tables (if not exists)
        $this->createTestTables();

        // Generate 1 million rows across tables
        $this->generateMillionRows();

        // Re-enable checks
        DB::statement('COMMIT;');
        DB::statement('SET UNIQUE_CHECKS=1;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        DB::statement('SET AUTOCOMMIT=1;');

        $totalTime = microtime(true) - $startTime;

        $this->command->info('✅ Completed in ' . number_format($totalTime, 2) . ' seconds!');
        $this->showStatistics();
    }

    private function createTestTables()
    {
        $this->command->info('Creating test tables...');

        // Table with index
        DB::statement('DROP TABLE IF EXISTS test_indexed');
        DB::statement('
            CREATE TABLE test_indexed (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(50),
                amount DECIMAL(10, 2),
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX idx_updated_at (updated_at),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        // Table WITHOUT index
        DB::statement('DROP TABLE IF EXISTS test_no_index');
        DB::statement('
            CREATE TABLE test_no_index (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                status VARCHAR(50),
                amount DECIMAL(10, 2),
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');

        $this->command->info('✓ Test tables created');
    }

    private function generateMillionRows()
    {
        $totalRecords = 1000000; // 1 MILLION
        $batchSize = 10000; // Insert 10K at a time (critical for speed!)

        $this->command->info("\n📦 Generating {$totalRecords} rows...");
        $this->command->line('This creates:');
        $this->command->line('  - 500K INDEXED rows (with index)');
        $this->command->line('  - 500K NO-INDEX rows (without index)');


        $progressBar = $this->command->getOutput()->createProgressBar($totalRecords);
        $progressBar->start();

        // Generate OLD data (70% of total - should NOT be backed up)
        $oldRecords = (int)($totalRecords * 0.7); // 700K old records
        $this->generateData('OLD', $oldRecords, $batchSize, $progressBar);

        // Generate RECENT data (30% of total - SHOULD be backed up)
        $recentRecords = $totalRecords - $oldRecords; // 300K recent records
        $this->generateData('RECENT', $recentRecords, $batchSize, $progressBar);

        $progressBar->finish();

    }

    private function generateData($type, $totalRecords, $batchSize, $progressBar)
    {
        $isOld = ($type === 'OLD');

        for ($i = 0; $i < $totalRecords; $i += $batchSize) {
            $indexed = [];
            $noIndex = [];

            for ($j = 0; $j < $batchSize && ($i + $j) < $totalRecords; $j++) {
                // Old data: 6-12 months ago
                // Recent data: last 3 days
                $timestamp = $isOld
                    ? Carbon::now()->subMonths(rand(6, 12))
                    : Carbon::now()->subHours(rand(1, 72));

                $record = [
                    'name' => $type . ' User ' . ($i + $j),
                    'email' => strtolower($type) . 'user' . ($i + $j) . '@test.com',
                    'status' => ['active', 'inactive', 'pending'][rand(0, 2)],
                    'amount' => rand(10, 10000) / 100,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                // Split 50/50 between indexed and non-indexed tables
                if (($i + $j) % 2 == 0) {
                    $indexed[] = $record;
                } else {
                    $noIndex[] = $record;
                }
            }

            // Bulk insert for speed
            if (!empty($indexed)) {
                DB::table('test_indexed')->insert($indexed);
            }

            if (!empty($noIndex)) {
                DB::table('test_no_index')->insert($noIndex);
            }

            $progressBar->advance(count($indexed) + count($noIndex));
        }
    }

    private function showStatistics()
    {
        $this->command->info("\n" . str_repeat('=', 70));
        $this->command->info('📊 DATABASE STATISTICS');
        $this->command->info(str_repeat('=', 70));

        $indexedCount = DB::table('test_indexed')->count();
        $noIndexCount = DB::table('test_no_index')->count();

        $this->command->info("test_indexed (WITH INDEX):    " . number_format($indexedCount) . " records");
        $this->command->info("test_no_index (NO INDEX):     " . number_format($noIndexCount) . " records");
        $this->command->info("TOTAL:                        " . number_format($indexedCount + $noIndexCount) . " records");


        // Calculate expected backup
        $cutoff = Carbon::now()->subDays(30);

        $recentIndexed = DB::table('test_indexed')
            ->where(function($q) use ($cutoff) {
                $q->where('updated_at', '>', $cutoff)
                  ->orWhere('created_at', '>', $cutoff);
            })
            ->count();

        $recentNoIndex = DB::table('test_no_index')
            ->where(function($q) use ($cutoff) {
                $q->where('updated_at', '>', $cutoff)
                  ->orWhere('created_at', '>', $cutoff);
            })
            ->count();

        $this->command->info('📋 EXPECTED BACKUP (last 30 days):');
        $this->command->info("test_indexed:    " . number_format($recentIndexed) . " rows");
        $this->command->info("test_no_index:   " . number_format($recentNoIndex) . " rows");
        $this->command->info("TOTAL TO BACKUP: " . number_format($recentIndexed + $recentNoIndex) . " rows");

        $this->command->info(str_repeat('=', 70));
        $this->command->info('✅ READY TO TEST!');
        $this->command->info(str_repeat('=', 70));

        $this->command->info('🧪 NOW RUN:');
        $this->command->info('  php artisan backup:incremental --report');

        $this->command->info('📊 EXPECTED RESULTS:');
        $this->command->info('  ⚡ test_indexed:  FAST (using index)');
        $this->command->info('  🐌 test_no_index: SLOW (full table scan)');
        $this->command->info('  📈 Difference should be 10-50x faster with index!');
    }
}
